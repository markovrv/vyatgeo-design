<script setup>
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useOoptSite, useOoptAdjacent, useOoptNearby, useOoptThumbnails, SITE_URL } from '@/composables/useOopt'
import CommentsSection from '@/components/CommentsSection.vue'
import { useStrings } from '@/i18n'

const { t } = useStrings()

const route = useRoute()
const { site, loading, error, fetchSite } = useOoptSite()
const { prev, next, fetchAdjacent } = useOoptAdjacent()
const { nearby, fetchNearby } = useOoptNearby()
const { thumbnails, fetchThumbnails } = useOoptThumbnails()

const NEARBY_RADIUS_KM = 200

const editUrl = computed(() => site.value ? `${SITE_URL}wp-admin/post.php?post=${site.value.id}&action=edit` : '')

function formatDistance(km) {
  if (km == null) return ''
  return km < 1 ? t('common.distMeters', { n: Math.round(km * 1000) }) : t('common.distKm', { n: km.toFixed(1) })
}
function formatArea(ha) {
  if (!ha) return ''
  const n = Number(ha)
  if (isNaN(n)) return ha.toString()
  return n >= 1000 ? t('views.NatureItemView.thousandHectares', { n: (n / 1000).toFixed(1) }) : t('views.NatureItemView.hectares', { n })
}
function speciesLabel(t) {
  return t.status ? `${t.name} (${t.status})` : t.name
}
function nearbyImg(n) { return thumbnails.value[n.imgId] || n.imgSrc }
watch(nearby, (items) => fetchThumbnails(items.map(n => n.imgId)))

function load(id) { fetchSite(id); fetchAdjacent(id); fetchNearby(id, NEARBY_RADIUS_KM) }
onMounted(() => load(route.params.id))
watch(() => route.params.id, (id) => load(id))
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">{{ t('common.home') }}</RouterLink><span>/</span>
      <RouterLink to="/nature">{{ t('views.NatureView.title') }}</RouterLink><span>/</span>
      <span class="current">{{ site?.title || t('common.ellipsis') }}</span>
    </nav>
    <p v-if="loading" class="state-msg">{{ t('common.loading') }}</p>
    <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
    <p v-else-if="!site" class="state-msg">{{ t('views.NatureItemView.notFound') }}</p>
    <template v-else>
      <section class="intro">
        <div class="tags">
          <span v-for="t in site.profileTerms" :key="t.slug" class="tag tag--teal">{{ t.name }}</span>
          <span v-for="t in site.districtTerms" :key="t.slug" class="tag tag--ochre">{{ t.name }}</span>
        </div>
        <h1 class="title">{{ site.title }}</h1>
        <p class="meta">{{ site.place }}<span v-if="site.area"> · {{ formatArea(site.area) }}</span></p>
      </section>
      <section v-if="site.summarize || site.content" class="section">
        <p v-if="site.summarize" class="summarize">{{ site.summarize }}</p>
        <div v-if="site.content" class="content" v-html="site.content" />
      </section>
      <section v-if="site.speciesTerms.length" class="section">
        <h2 class="heading">{{ t('views.NatureItemView.speciesHeading') }}</h2>
        <div class="species-chips">
          <span v-for="t in site.speciesTerms" :key="t.name" class="species-chip">{{ speciesLabel(t) }}</span>
        </div>
      </section>
      <nav v-if="prev || next" class="item-nav">
        <RouterLink v-if="prev" :to="`/nature/${prev.id}`" class="nav-card nav-card--prev">
          <div class="nav-thumb" :style="{ backgroundImage: `url(${prev.imgSrc})` }" />
          <div class="nav-text">
            <span class="nav-direction"><span class="nav-direction-full">{{ t('common.prevFull') }}</span><span class="nav-direction-short">{{ t('common.prevShort') }}</span></span>
            <span class="nav-title" :title="prev.title">{{ prev.title }}</span>
          </div>
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />
        <RouterLink v-if="next" :to="`/nature/${next.id}`" class="nav-card nav-card--next">
          <div class="nav-text">
            <span class="nav-direction"><span class="nav-direction-full">{{ t('common.nextFull') }}</span><span class="nav-direction-short">{{ t('common.nextShort') }}</span></span>
            <span class="nav-title" :title="next.title">{{ next.title }}</span>
          </div>
          <div class="nav-thumb" :style="{ backgroundImage: `url(${next.imgSrc})` }" />
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />
      </nav>
      <div class="back-bar">
        <RouterLink to="/nature" class="cta-btn">{{ t('views.NatureItemView.backToCatalog') }}</RouterLink>
        <a v-if="editUrl" :href="editUrl" target="_blank" rel="noopener" class="edit-link">{{ t('common.edit') }}</a>
      </div>
      <section v-if="nearby.length" class="section nearby-section">
        <h2 class="nearby-heading">{{ t('views.NatureItemView.nearbyHeading') }}</h2>
        <div class="nearby-grid">
          <RouterLink v-for="n in nearby" :key="n.id" :to="`/nature/${n.id}`" class="nearby-card">
            <div class="nearby-img" :style="{ backgroundImage: `url(${nearbyImg(n)})` }" />
            <div class="nearby-body">
              <span v-if="n.distanceKm != null" class="nearby-distance">{{ formatDistance(n.distanceKm) }}</span>
              <h3 class="nearby-title">{{ n.title }}</h3>
              <p v-if="n.place" class="nearby-place">{{ n.place }}</p>
            </div>
          </RouterLink>
        </div>
      </section>
      <CommentsSection :post-id="site.id" :comment-status="site.commentStatus" />
    </template>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.state-msg { text-align: center; color: var(--color-muted); padding: var(--space-5) var(--space-3); }
.state-msg--error { color: var(--color-error, #b3261e); }
.breadcrumb { max-width: 1000px; margin: 0 auto; padding: var(--space-2) var(--space-3) 0; display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; font-size: 13px; color: var(--color-muted) }
.breadcrumb a { color: var(--color-ochre); text-decoration: none }
.current { color: var(--color-ink) }
.intro { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) 0; text-align: center }
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap; justify-content: center; margin-bottom: 12px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin: 0 }
.section { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5) }
.heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 12px }
.summarize { font-family: var(--font-body); font-size: 17px; font-weight: 500; line-height: 1.7; color: var(--color-ink); margin: 0 0 var(--space-3) }
.content { line-height: 1.75; color: var(--color-ink) }
.content :deep(img) { max-width: 100%; border-radius: var(--radius); }
.species-chips { display: flex; flex-wrap: wrap; gap: 8px }
.species-chip { font-size: 13px; padding: 6px 14px; background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius); color: var(--color-ink) }
.item-nav { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3); display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: var(--space-2); }
.nav-card { display: flex; align-items: center; gap: var(--space-2); text-decoration: none; color: inherit; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); padding: 10px; transition: border-color 200ms, transform 200ms; }
.nav-card:hover { border-color: var(--color-ochre); transform: translateY(-2px); }
.nav-card--next { justify-content: flex-end; text-align: right; }
.nav-card--empty { border-style: solid; border-color: transparent; background: none; }
.nav-thumb { flex-shrink: 0; width: 56px; height: 56px; border-radius: var(--radius); background-size: cover; background-position: center; background-color: var(--color-birch); }
.nav-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.nav-direction { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-teal); }
.nav-direction-short { display: none; }
.nav-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--color-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
@media (max-width: 767px) { .nav-direction-full { display: none; } .nav-direction-short { display: inline; } }
.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }
.nearby-section { padding-top: 0; }
.nearby-heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2); text-align: center; }
.nearby-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-2); }
.nearby-card { display: block; text-decoration: none; color: inherit; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms; }
.nearby-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.nearby-img { width: 100%; aspect-ratio: 4/3; background-size: cover; background-position: center; background-color: var(--color-birch); }
.nearby-body { padding: var(--space-2); }
.nearby-distance { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; color: var(--color-bg); background: var(--color-teal); padding: 2px 10px; border-radius: 999px; margin-bottom: 6px; }
.nearby-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; margin: 0; color: var(--color-ink); line-height: 1.3; }
.nearby-place { font-size: 12px; color: var(--color-muted); margin: 4px 0 0; }
.cta-btn { display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-body); font-size: 13px; font-weight: 700; padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface); text-decoration: none; min-height: 44px; letter-spacing: 0.03em; clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px); transition: background 300ms, transform 150ms; }
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }
.edit-link { display: inline-flex; align-items: center; min-height: 44px; padding: 10px 6px; font-family: var(--font-body); font-size: 12px; color: var(--color-muted); text-decoration: none; opacity: 0.65; transition: opacity 150ms, color 150ms; }
.edit-link:hover { opacity: 1; color: var(--color-teal); text-decoration: underline; text-underline-offset: 3px; }
</style>
