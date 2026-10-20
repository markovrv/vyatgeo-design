<script setup>
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useHero, useHeroAdjacent, useHeroNearby, useHeroThumbnails, SITE_URL } from '@/composables/useHeroes'
import CommentsSection from '@/components/CommentsSection.vue'
import { useStrings } from '@/i18n'

const { t } = useStrings()

const route = useRoute()
const { hero, loading, error, fetchHero } = useHero()
const { prev, next, fetchAdjacent } = useHeroAdjacent()
const { nearby, fetchNearby } = useHeroNearby()
const { thumbnails, fetchThumbnails } = useHeroThumbnails()

const NEARBY_RADIUS_KM = 200

const editUrl = computed(() => hero.value ? `${SITE_URL}wp-admin/post.php?post=${hero.value.id}&action=edit` : '')

function formatDistance(km) {
  if (km == null) return ''
  return km < 1 ? t('common.distMeters', { n: Math.round(km * 1000) }) : t('common.distKm', { n: km.toFixed(1) })
}
function nearbyImg(n) { return thumbnails.value[n.imgId] || n.imgSrc }
watch(nearby, (items) => fetchThumbnails(items.map(n => n.imgId)))

function load(id) { fetchHero(id); fetchAdjacent(id); fetchNearby(id, NEARBY_RADIUS_KM) }
onMounted(() => load(route.params.id))
watch(() => route.params.id, (id) => load(id))
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">{{ t('common.home') }}</RouterLink><span>/</span>
      <RouterLink to="/victory">{{ t('views.HeroesView.title') }}</RouterLink><span>/</span>
      <span class="current">{{ hero?.title || t('common.ellipsis') }}</span>
    </nav>
    <p v-if="loading" class="state-msg">{{ t('common.loading') }}</p>
    <p v-else-if="error" class="state-msg state-msg--error">{{ t('common.loadError') }}</p>
    <p v-else-if="!hero" class="state-msg">{{ t('views.HeroItemView.notFound') }}</p>
    <template v-else>
      <section class="intro">
        <img v-if="hero.imgSrc" :src="hero.imgSrc" :alt="hero.title" class="portrait" />
        <div class="tags">
          <span v-if="hero.statusName" class="tag tag--teal">{{ hero.statusName }}</span>
        </div>
        <h1 class="title">{{ hero.title }}</h1>
        <p v-if="hero.birthDate || hero.deathDate" class="meta">
          {{ hero.birthDate }}<template v-if="hero.deathDate"> — {{ hero.deathDate }}</template>
        </p>
        <p v-if="hero.birthPlace" class="meta">{{ hero.birthPlace }}</p>
      </section>
      <section v-if="hero.feat" class="section">
        <h2 class="heading">{{ t('views.HeroItemView.featHeading') }}</h2>
        <p class="text">{{ hero.feat }}</p>
      </section>
      <section v-if="hero.profession || hero.awards || hero.connection" class="section">
        <dl class="info-grid">
          <template v-if="hero.profession"><dt>{{ t('views.HeroItemView.professionLabel') }}</dt><dd>{{ hero.profession }}</dd></template>
          <template v-if="hero.awards"><dt>{{ t('views.HeroItemView.awardsLabel') }}</dt><dd>{{ hero.awards }}</dd></template>
          <template v-if="hero.connection"><dt>{{ t('views.HeroItemView.connectionLabel') }}</dt><dd>{{ hero.connection }}</dd></template>
        </dl>
      </section>
      <section v-if="hero.content" class="section">
        <div class="content" v-html="hero.content" />
      </section>
      <nav v-if="prev || next" class="item-nav">
        <RouterLink v-if="prev" :to="`/victory/${prev.id}`" class="nav-card nav-card--prev">
          <div class="nav-thumb" :style="{ backgroundImage: `url(${prev.imgSrc})` }" />
          <div class="nav-text">
            <span class="nav-direction"><span class="nav-direction-full">{{ t('common.prevFull') }}</span><span class="nav-direction-short">{{ t('common.prevShort') }}</span></span>
            <span class="nav-title" :title="prev.title">{{ prev.title }}</span>
          </div>
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />
        <RouterLink v-if="next" :to="`/victory/${next.id}`" class="nav-card nav-card--next">
          <div class="nav-text">
            <span class="nav-direction"><span class="nav-direction-full">{{ t('common.nextFull') }}</span><span class="nav-direction-short">{{ t('common.nextShort') }}</span></span>
            <span class="nav-title" :title="next.title">{{ next.title }}</span>
          </div>
          <div class="nav-thumb" :style="{ backgroundImage: `url(${next.imgSrc})` }" />
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />
      </nav>
      <div class="back-bar">
        <RouterLink to="/victory" class="cta-btn">{{ t('views.HeroItemView.backToList') }}</RouterLink>
        <a v-if="editUrl" :href="editUrl" target="_blank" rel="noopener" class="edit-link">{{ t('common.edit') }}</a>
      </div>
      <section v-if="nearby.length" class="section nearby-section">
        <h2 class="nearby-heading">{{ t('views.HeroItemView.nearbyHeading') }}</h2>
        <div class="nearby-grid">
          <RouterLink v-for="n in nearby" :key="n.id" :to="`/victory/${n.id}`" class="nearby-card">
            <div class="nearby-img" :style="{ backgroundImage: `url(${nearbyImg(n)})` }" />
            <div class="nearby-body">
              <span v-if="n.distanceKm != null" class="nearby-distance">{{ formatDistance(n.distanceKm) }}</span>
              <h3 class="nearby-title">{{ n.title }}</h3>
              <p v-if="n.place" class="nearby-place">{{ n.place }}</p>
            </div>
          </RouterLink>
        </div>
      </section>
      <CommentsSection :post-id="hero.id" :comment-status="hero.commentStatus" />
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
.portrait { display: block; width: 220px; aspect-ratio: 3/4; object-fit: cover; object-position: center 20%; border-radius: var(--radius); border: 1.5px dashed var(--color-border); background: var(--color-birch); margin: 0 auto var(--space-3); }
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap; justify-content: center; margin-bottom: 12px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin: 0 0 2px }
.section { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5) }
.heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 12px }
.text { line-height: 1.75; color: var(--color-ink); margin: 0 }
.info-grid { display: grid; grid-template-columns: 140px 1fr; gap: 12px var(--space-3); }
.info-grid dt { font-size: 13px; font-weight: 700; color: var(--color-teal); text-transform: uppercase; letter-spacing: 0.06em; }
.info-grid dd { font-size: 15px; color: var(--color-ink); margin: 0; line-height: 1.6; }
.content { line-height: 1.75; color: var(--color-ink) }
.content :deep(img) { max-width: 100%; border-radius: var(--radius); }
.item-nav { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3); display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: var(--space-2); }
.nav-card { display: flex; align-items: center; gap: var(--space-2); text-decoration: none; color: inherit; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); padding: 10px; transition: border-color 200ms, transform 200ms; }
.nav-card:hover { border-color: var(--color-ochre); transform: translateY(-2px); }
.nav-card--next { justify-content: flex-end; text-align: right; }
.nav-card--empty { border-style: solid; border-color: transparent; background: none; }
.nav-thumb { flex-shrink: 0; width: 56px; height: 56px; border-radius: var(--radius); background-size: cover; background-position: center 20%; background-color: var(--color-birch); }
.nav-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.nav-direction { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-teal); }
.nav-direction-short { display: none; }
.nav-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--color-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
@media (max-width: 767px) { .nav-direction-full { display: none; } .nav-direction-short { display: inline; } .info-grid { grid-template-columns: 1fr; gap: 4px var(--space-1); } .info-grid dt { margin-top: var(--space-2); } }
.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }
.nearby-section { padding-top: 0; }
.nearby-heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2); text-align: center; }
.nearby-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-2); }
.nearby-card { display: block; text-decoration: none; color: inherit; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms; }
.nearby-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.nearby-img { width: 100%; aspect-ratio: 4/3; background-size: cover; background-position: center 20%; background-color: var(--color-birch); }
.nearby-body { padding: var(--space-2); }
.nearby-distance { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; color: var(--color-bg); background: var(--color-teal); padding: 2px 10px; border-radius: 999px; margin-bottom: 6px; }
.nearby-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; margin: 0; color: var(--color-ink); line-height: 1.3; }
.nearby-place { font-size: 12px; color: var(--color-muted); margin: 4px 0 0; }
.cta-btn { display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-body); font-size: 13px; font-weight: 700; padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface); text-decoration: none; min-height: 44px; letter-spacing: 0.03em; clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px); transition: background 300ms, transform 150ms; }
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }
.edit-link { display: inline-flex; align-items: center; min-height: 44px; padding: 10px 6px; font-family: var(--font-body); font-size: 12px; color: var(--color-muted); text-decoration: none; opacity: 0.65; transition: opacity 150ms, color 150ms; }
.edit-link:hover { opacity: 1; color: var(--color-teal); text-decoration: underline; text-underline-offset: 3px; }
</style>
