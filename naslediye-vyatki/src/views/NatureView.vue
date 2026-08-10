<script setup>
import { computed, nextTick, onMounted, watch } from 'vue'
import { RouterLink, onBeforeRouteLeave } from 'vue-router'
import heroImg from '@/assets/img/nature.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import Pagination from '@/components/Pagination.vue'
import MultiSelectFilter from '@/components/MultiSelectFilter.vue'
import NatureMap from '@/components/NatureMap.vue'
import NatureControls from '@/components/NatureControls.vue'
import { useOoptSites, useOoptThumbnails, natureCatalogState as state } from '@/composables/useOopt'
import { useStrings } from '@/i18n'

const { t } = useStrings()

const { sites, loading, error, fetchSites } = useOoptSites()
const { thumbnails, fetchThumbnails } = useOoptThumbnails()

// Опции фильтров строим из уже загруженных данных, а не хардкодим — slug'и
// терминов у oopt_district/oopt_profile WP генерирует без транслитерации
// (процент-кодированная кириллица), любой захардкоженный список неизбежно
// разойдётся с реальными значениями (см. историю с hero_status).
function buildTermOptions(items, termsKey) {
  const seen = new Map()
  for (const s of items) {
    for (const t of s[termsKey]) {
      if (!seen.has(t.slug)) seen.set(t.slug, { id: t.id, slug: t.slug, name: t.name, count: 0 })
    }
  }
  return [...seen.values()]
}

const districtOptions = computed(() => buildTermOptions(sites.value, 'districtTerms'))
const profileOptions = computed(() => buildTermOptions(sites.value, 'profileTerms'))
const speciesOptions = computed(() => buildTermOptions(sites.value, 'speciesTerms'))

function onMultiSelectChange(field, value) {
  state[field] = value
  state.page = 1
}

const filtered = computed(() => {
  const q = state.search.trim().toLowerCase()
  return sites.value.filter(s => {
    if (state.district.length && !state.district.some(sl => s.districtTerms.some(t => t.slug === sl))) return false
    if (state.profile.length && !state.profile.some(sl => s.profileTerms.some(t => t.slug === sl))) return false
    if (state.species.length && !state.species.some(sl => s.speciesTerms.some(t => t.slug === sl))) return false
    if (q) return s.title.toLowerCase().includes(q) || s.place.toLowerCase().includes(q)
    return true
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / state.perPage)))
const pageItems = computed(() => {
  const start = (state.page - 1) * state.perPage
  return filtered.value.slice(start, start + state.perPage)
})

function cardImg(a) { return thumbnails.value[a.imgId] || a.imgSrc }

watch([pageItems, () => state.viewMode], ([items, mode]) => {
  if (mode === 'catalog') fetchThumbnails(items.map(a => a.imgId))
}, { immediate: true })

function changePage(p) {
  state.page = p
  window.scrollTo({ top: document.querySelector('.content')?.offsetTop - 100 || 0, behavior: 'smooth' })
}
function changePerPage(n) { state.perPage = n; state.page = 1 }

onMounted(async () => {
  await fetchSites()
  await nextTick()
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))
  if (state.scrollY) window.scrollTo(0, state.scrollY)
})
onBeforeRouteLeave(() => { state.scrollY = window.scrollY })
</script>

<template>
  <CatalogLayout :heroImg="heroImg" :eyebrow="t('views.NatureView.eyebrow')" :title="t('views.NatureView.title')" :subtitle="t('views.NatureView.subtitle')" :gridCount="state.viewMode === 'catalog' ? filtered.length : 0">
    <template #heading>{{ t('views.NatureView.heading') }}</template>
    <template #description>
      <p style="text-align:center; max-width:700px; margin: 0 auto var(--space-3); line-height: 1.75;">
        {{ t('views.NatureView.description') }}
      </p>
    </template>

    <template #map>
      <div class="map-shell">
        <div class="controls-group" :class="{ 'controls-group--floating': state.viewMode === 'map' }">
          <NatureControls />
          <div v-if="state.filtersOpen" class="filters-panel">
            <MultiSelectFilter :label="t('views.NatureView.districtLabel')" :options="districtOptions" :modelValue="state.district" @update:modelValue="v => onMultiSelectChange('district', v)" />
            <MultiSelectFilter :label="t('views.NatureView.profileLabel')" :options="profileOptions" :modelValue="state.profile" @update:modelValue="v => onMultiSelectChange('profile', v)" />
            <MultiSelectFilter :label="t('views.NatureView.speciesLabel')" :options="speciesOptions" :modelValue="state.species" @update:modelValue="v => onMultiSelectChange('species', v)" />
          </div>
        </div>
        <template v-if="state.viewMode === 'map'">
          <p v-if="loading" class="state-msg">{{ t('common.loadingMap') }}</p>
          <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
          <NatureMap v-else :points="filtered" />
        </template>
      </div>
    </template>

    <template #grid>
      <template v-if="state.viewMode === 'catalog'">
        <p v-if="loading" class="state-msg">{{ t('common.loading') }}</p>
        <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
        <p v-else-if="pageItems.length === 0" class="state-msg">{{ t('common.emptyQuery') }}</p>
        <RouterLink v-for="s in pageItems" :key="s.id" :to="`/nature/${s.id}`" class="card">
          <div class="card-img" :style="{ backgroundImage: `url(${cardImg(s)})` }" role="img" :aria-label="s.title" />
          <div class="card-body">
            <div class="tags">
              <span v-for="t in s.profileTerms" :key="t.slug" class="tag tag--teal">{{ t.name }}</span>
              <span v-for="t in s.districtTerms" :key="t.slug" class="tag tag--ochre">{{ t.name }}</span>
            </div>
            <h3 class="card-title">{{ s.title }}</h3>
            <p class="card-meta">{{ s.place }}<span v-if="s.area">{{ t('views.NatureView.areaSuffix', { area: s.area }) }}</span></p>
          </div>
        </RouterLink>
      </template>
    </template>

    <template v-if="state.viewMode === 'catalog'" #pagination>
      <Pagination :page="state.page" :totalPages="totalPages" :total="filtered.length" :perPage="state.perPage" @update:page="changePage" @update:perPage="changePerPage" />
    </template>
  </CatalogLayout>

  <section class="cta">
    <h2 class="cta-heading">{{ t('views.NatureView.ctaHeading') }}</h2>
    <p class="cta-sub">{{ t('views.NatureView.ctaSub') }}</p>
    <button type="button" class="cta-btn">{{ t('views.NatureView.ctaBtn') }}</button>
  </section>
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

.map-shell { position: relative; margin-bottom: var(--space-3); }
/* Строка управления (переключатель + поиск + «Фильтры») и разворачивающаяся
   под ней панель — общая floating-группа: плавает над картой в режиме карты
   (как раньше сами контролы), обычная колонка в потоке над сеткой в режиме
   каталога. Тот же приём отступов, что и в других модулях с картой, просто
   применённый к группе целиком, а не только к строке контролов — иначе
   панель фильтров в floating-режиме не имела бы точки привязки под строкой. */
.controls-group { display: flex; flex-direction: column; margin: 0 16px var(--space-2); padding-top: 16px; }
.controls-group--floating { position: absolute; top: 16px; left: 16px; right: 16px; z-index: 20; margin: 0; padding-top: 0; }
.cta { background: var(--color-birch); padding: var(--space-5) var(--space-3); text-align: center }
.cta-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); margin: 0 0 var(--space-2) }
.cta-sub { font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-3) }
.cta-btn { display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-body); font-size: 14px; font-weight: 500; padding: 14px 32px; border: none; cursor: pointer; background: var(--color-ochre); color: var(--color-bg); min-height: 44px; clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px); transition: background 300ms; }
.cta-btn:hover { background: var(--color-teal) }
.card { display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit; transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out); }
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 2px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-muted); margin: 0 }
</style>
