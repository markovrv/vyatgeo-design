<script setup>
import { computed, nextTick, onMounted, watch } from 'vue'
import { RouterLink, onBeforeRouteLeave } from 'vue-router'
import heroImg from '@/assets/img/archeology.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import Pagination from '@/components/Pagination.vue'
import MultiSelectFilter from '@/components/MultiSelectFilter.vue'
import ArcheologyMap from '@/components/ArcheologyMap.vue'
import ArcheologyControls from '@/components/ArcheologyControls.vue'
import { useArcheologySites, useArcheologyThumbnails, archeologyCatalogState as state } from '@/composables/useArcheology'
import { useStrings } from '@/i18n'

const { t } = useStrings()

const { sites, loading, error, fetchSites } = useArcheologySites()
const { thumbnails, fetchThumbnails } = useArcheologyThumbnails()

// era/type — короткие ключи из bulk-контракта archeology/v1/objects (не
// WP-таксономия), фиксированный набор из 5 значений каждый — хардкод здесь
// уместен (в отличие от oopt_district/oopt_profile у Природы, где то же
// самое было бы багом — см. историю с hero_status/oopt_profile).
const eraOptions = [
  { id: 'mezolit', slug: 'mezolit', name: t('composables.useArcheology.eraMezolit'), count: 0 },
  { id: 'neolit', slug: 'neolit', name: t('composables.useArcheology.eraNeolit'), count: 0 },
  { id: 'bronze', slug: 'bronze', name: t('composables.useArcheology.eraBronze'), count: 0 },
  { id: 'iron', slug: 'iron', name: t('composables.useArcheology.eraIron'), count: 0 },
  { id: 'medieval', slug: 'medieval', name: t('composables.useArcheology.eraMedieval'), count: 0 },
]

const typeOptions = [
  { id: 'gorodishche', slug: 'gorodishche', name: t('composables.useArcheology.typeGorodishche'), count: 0 },
  { id: 'selishche', slug: 'selishche', name: t('composables.useArcheology.typeSelishche'), count: 0 },
  { id: 'stoyanka', slug: 'stoyanka', name: t('composables.useArcheology.typeStoyanka'), count: 0 },
  { id: 'mogilnik', slug: 'mogilnik', name: t('composables.useArcheology.typeMogilnik'), count: 0 },
  { id: 'klad', slug: 'klad', name: t('composables.useArcheology.typeKlad'), count: 0 },
]

function onMultiSelectChange(field, value) {
  state[field] = value
  state.page = 1
}

const filtered = computed(() => {
  const q = state.search.trim().toLowerCase()
  return sites.value.filter(s => {
    if (state.era.length && !state.era.includes(s.era)) return false
    if (state.type.length && !state.type.includes(s.type)) return false
    if (q) return s.title.toLowerCase().includes(q) || s.place.toLowerCase().includes(q)
    return true
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / state.perPage)))
const pageItems = computed(() => {
  const start = (state.page - 1) * state.perPage
  return filtered.value.slice(start, start + state.perPage)
})

function cardImg(a) {
  return thumbnails.value[a.imgId] || a.imgSrc
}

watch([pageItems, () => state.viewMode], ([items, mode]) => {
  if (mode === 'catalog') fetchThumbnails(items.map(a => a.imgId))
}, { immediate: true })

function changePage(p) {
  state.page = p
  window.scrollTo({ top: document.querySelector('.content')?.offsetTop - 100 || 0, behavior: 'smooth' })
}

function changePerPage(n) {
  state.perPage = n
  state.page = 1
}

onMounted(async () => {
  await fetchSites()
  await nextTick()
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))
  if (state.scrollY) window.scrollTo(0, state.scrollY)
})

onBeforeRouteLeave(() => {
  state.scrollY = window.scrollY
})
</script>

<template>
  <CatalogLayout
    :heroImg="heroImg"
    :eyebrow="t('views.ArcheologyView.eyebrow')"
    :title="t('views.ArcheologyView.title')"
    :subtitle="t('views.ArcheologyView.subtitle')"
    :gridCount="state.viewMode === 'catalog' ? filtered.length : 0"
  >
    <template #heading>{{ t('views.ArcheologyView.heading') }}</template>
    <template #description>
      <p style="text-align:center; max-width:700px; margin: 0 auto var(--space-3); line-height: 1.75;">
        {{ t('views.ArcheologyView.description') }}
      </p>
    </template>

    <template #map>
      <div class="map-shell">
        <div class="controls-group" :class="{ 'controls-group--floating': state.viewMode === 'map' }">
          <ArcheologyControls />
          <div v-if="state.filtersOpen" class="filters-panel">
            <MultiSelectFilter :label="t('views.ArcheologyView.eraLabel')" :options="eraOptions" :modelValue="state.era" @update:modelValue="v => onMultiSelectChange('era', v)" />
            <MultiSelectFilter :label="t('views.ArcheologyView.typeLabel')" :options="typeOptions" :modelValue="state.type" @update:modelValue="v => onMultiSelectChange('type', v)" />
          </div>
        </div>
        <template v-if="state.viewMode === 'map'">
          <p v-if="loading" class="state-msg">{{ t('common.loadingMap') }}</p>
          <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
          <ArcheologyMap v-else :points="filtered" />
        </template>
      </div>
    </template>

    <template #grid>
      <template v-if="state.viewMode === 'catalog'">
        <p v-if="loading" class="state-msg">{{ t('common.loading') }}</p>
        <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
        <p v-else-if="pageItems.length === 0" class="state-msg">{{ t('common.emptyQuery') }}</p>
        <RouterLink v-for="s in pageItems" :key="s.id" :to="`/archeology/${s.id}`" class="card">
          <div class="card-img" :style="{ backgroundImage: `url(${cardImg(s)})` }" role="img" :aria-label="s.title" />
          <div class="card-body">
            <div class="tags">
              <span v-if="s.eraLabel" class="tag tag--teal">{{ s.eraLabel }}</span>
              <span v-if="s.typeLabel" class="tag tag--ochre">{{ s.typeLabel }}</span>
            </div>
            <h3 class="card-title">{{ s.title }}</h3>
            <p v-if="s.place" class="card-meta">{{ s.place }}</p>
          </div>
        </RouterLink>
      </template>
    </template>

    <template v-if="state.viewMode === 'catalog'" #pagination>
      <Pagination
        :page="state.page" :totalPages="totalPages" :total="filtered.length" :perPage="state.perPage"
        @update:page="changePage" @update:perPage="changePerPage"
      />
    </template>
  </CatalogLayout>
</template>

<style scoped>
.state-msg { grid-column: 1 / -1; text-align: center; color: var(--color-muted); padding: var(--space-4) 0; }
.state-msg--error { color: var(--color-error, #b3261e); }

.filters-panel {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-2);
  margin-top: var(--space-2); padding: var(--space-2);
  background: var(--color-birch); border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  align-items: start;
}

/* Строка управления (переключатель + поиск + «Фильтры») и разворачивающаяся
   под ней панель с эпохой/типом — общая floating-группа: плавает над картой
   в режиме карты, обычная колонка в потоке над сеткой в режиме каталога.
   Тот же паттерн, что и в NatureView.vue/NatureControls.vue. */
.map-shell { position: relative; margin-bottom: var(--space-3); }
.controls-group { display: flex; flex-direction: column; margin: 0 16px var(--space-2); padding-top: 16px; }
.controls-group--floating {
  position: absolute; top: 16px; left: 16px; right: 16px; z-index: 20; margin: 0; padding-top: 0;
}

.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 2px; color: var(--color-ink) }
.card-meta { font-size: 13px; color: var(--color-teal); margin: 0 }
</style>
