<script setup>
import { computed, nextTick, onMounted, watch } from 'vue'
import { RouterLink, onBeforeRouteLeave } from 'vue-router'
import heroImg from '@/assets/img/victory-hero.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import Pagination from '@/components/Pagination.vue'
import FilterBar from '@/components/FilterBar.vue'
import HeroMap from '@/components/HeroMap.vue'
import HeroControls from '@/components/HeroControls.vue'
import { useHeroes, useHeroThumbnails, heroesCatalogState as state } from '@/composables/useHeroes'
import { useStrings } from '@/i18n'

const { t } = useStrings()

const { heroes, loading, error, fetchHeroes } = useHeroes()
const { thumbnails, fetchThumbnails } = useHeroThumbnails()

// Реальные slug'и термов hero_status — WP генерирует их из кириллического
// названия без транслитерации (процент-кодированная строка вида
// "%d0%b8%d1%81..."), а не "istoricheskaya"/"sovremennaya" — хардкодить
// такие значения нельзя, собираем опции из уже загруженных данных.
const statusOptions = computed(() => {
  const seen = new Map()
  for (const h of heroes.value) {
    if (h.statusSlug && !seen.has(h.statusSlug)) seen.set(h.statusSlug, h.statusName)
  }
  return [
    { value: 'all', label: t('views.HeroesView.statusAll') },
    ...[...seen.entries()].map(([value, label]) => ({ value, label })),
  ]
})

const filtered = computed(() => {
  const q = state.search.trim().toLowerCase()
  return heroes.value.filter(h => {
    if (state.status !== 'all' && h.statusSlug !== state.status) return false
    if (q) return h.title.toLowerCase().includes(q) || h.place.toLowerCase().includes(q) || h.summarize.toLowerCase().includes(q)
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
  await fetchHeroes()
  await nextTick()
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))
  if (state.scrollY) window.scrollTo(0, state.scrollY)
})
onBeforeRouteLeave(() => { state.scrollY = window.scrollY })
</script>

<template>
  <CatalogLayout :heroImg="heroImg" :eyebrow="t('views.HeroesView.eyebrow')" :title="t('views.HeroesView.title')" :subtitle="t('views.HeroesView.subtitle')" :gridCount="state.viewMode === 'catalog' ? filtered.length : 0">
    <template #heading>{{ t('views.HeroesView.heading') }}</template>
    <template #description>
      <p style="text-align:center; max-width:700px; margin: 0 auto var(--space-3); line-height: 1.75;">
        {{ t('views.HeroesView.description') }}
      </p>
    </template>
    <template #filters>
      <div class="filters-block">
        <div class="filters-row">
          <FilterBar :label="t('views.HeroesView.statusLabel')" :options="statusOptions" :active="state.status" @select="v => { state.status = v; state.page = 1 }" />
        </div>
      </div>
    </template>
    <template #map>
      <div class="map-shell">
        <HeroControls class="controls" :class="{ 'controls--floating': state.viewMode === 'map' }" />
        <template v-if="state.viewMode === 'map'">
          <p v-if="loading" class="state-msg">{{ t('common.loadingMap') }}</p>
          <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
          <HeroMap v-else :points="filtered" />
        </template>
      </div>
    </template>
    <template #grid>
      <template v-if="state.viewMode === 'catalog'">
        <p v-if="loading" class="state-msg">{{ t('common.loading') }}</p>
        <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
        <p v-else-if="pageItems.length === 0" class="state-msg">{{ t('common.emptyQuery') }}</p>
        <RouterLink v-for="h in pageItems" :key="h.id" :to="`/victory/${h.id}`" class="card">
          <div class="card-img" :style="{ backgroundImage: `url(${cardImg(h)})` }" role="img" :aria-label="h.title" />
          <div class="card-body">
            <div class="tags">
              <span v-if="h.statusName" class="tag tag--teal">{{ h.statusName }}</span>
            </div>
            <h3 class="card-title">{{ h.title }}</h3>
            <p v-if="h.place" class="card-meta">{{ h.place }}</p>
            <p v-if="h.summarize" class="card-desc">{{ h.summarize }}</p>
          </div>
        </RouterLink>
      </template>
    </template>
    <template v-if="state.viewMode === 'catalog'" #pagination>
      <Pagination :page="state.page" :totalPages="totalPages" :total="filtered.length" :perPage="state.perPage" @update:page="changePage" @update:perPage="changePerPage" />
    </template>
  </CatalogLayout>
  <section class="cta">
    <h2 class="cta-heading">{{ t('views.HeroesView.ctaHeading') }}</h2>
    <p class="cta-sub">{{ t('views.HeroesView.ctaSub') }}</p>
    <button type="button" class="cta-btn">{{ t('views.HeroesView.ctaBtn') }}</button>
  </section>
</template>

<style scoped>
.state-msg { grid-column: 1 / -1; text-align: center; color: var(--color-muted); padding: var(--space-4) 0; }
.state-msg--error { color: var(--color-error, #b3261e); }
.filters-block { display: flex; flex-direction: column; gap: var(--space-2); }
.filters-row { display: flex; flex-wrap: wrap; gap: var(--space-2); justify-content: center; }
.map-shell { position: relative; margin-bottom: var(--space-3); }
.controls { margin: 0 16px var(--space-2); padding-top: 16px; }
.controls--floating { position: absolute; top: 16px; left: 16px; right: 16px; z-index: 20; margin: 0; padding-top: 0; }
.cta { background: var(--color-birch); padding: var(--space-5) var(--space-3); text-align: center }
.cta-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); margin: 0 0 var(--space-2) }
.cta-sub { font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-3) }
.cta-btn { display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-body); font-size: 14px; font-weight: 500; padding: 14px 32px; border: none; cursor: pointer; background: var(--color-ochre); color: var(--color-bg); min-height: 44px; clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px); transition: background 300ms; }
.cta-btn:hover { background: var(--color-teal) }
.card { display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit; transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out); }
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center 20% }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 2px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-teal); margin-bottom: 4px }
.card-desc { font-size: 13px; color: var(--color-ink); line-height: 1.5; margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
