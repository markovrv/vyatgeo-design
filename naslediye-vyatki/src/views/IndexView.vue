<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useStrings } from '@/i18n'
import { useScrollFadeIn } from '@/composables/useScrollFadeIn'
import { useHistoryMilestones } from '@/composables/useHistory'
import KremlinVideo from '@/components/KremlinVideo.vue'
import heroImg from '@/assets/img/hero-trifonov.jpg'
import archImg from '@/assets/img/architecture-dom-vitberga.jpg'
import craftDymkovo from '@/assets/img/dymkovo-toys.jpg'
import craftKapokoren from '@/assets/img/kapokoren.jpg'
import craftKruzhevo from '@/assets/img/kruzhevo.jpg'
import victoryFacesImg from '@/assets/img/victory-faces.jpg'
import ethnographyImg from '@/assets/img/ethnography.jpg'
import historicCitiesImg from '@/assets/img/historic-cities.jpg'
import natureImg from '@/assets/img/nature.jpg'
import archeologyImg from '@/assets/img/archeology.jpg'
import architectureKirovImg from '@/assets/img/architecture-kirov.jpg'

const { t } = useStrings()

const { fadeStyle } = useScrollFadeIn()

const heroOffset = ref(0)
const archOffset = ref(0)

const craftIndex = ref(0)

function onScroll() {
  heroOffset.value = window.scrollY * 0.4
  const el = document.getElementById('archParallax')
  if (el) {
    const rect = el.getBoundingClientRect()
    const s = -rect.top
    if (s > -el.offsetHeight && s < window.innerHeight) archOffset.value = s * 0.25
  }
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})
onUnmounted(() => window.removeEventListener('scroll', onScroll))

// Вехи блока "Вятский край: семь веков истории" — реальные события Кирова
// (модуль "Города"), не выдуманный список дат, см. useHistoryMilestones.
const { milestones: timelineItems, fetchMilestones } = useHistoryMilestones()
onMounted(() => fetchMilestones('kirov'))

const modules = [
  { title: t('views.IndexView.moduleVictory'), to: '/victory', img: victoryFacesImg, desc: t('views.IndexView.moduleVictoryDesc'), recommended: true },
  { title: t('views.IndexView.moduleEthnography'), to: '/ethnography', img: ethnographyImg, desc: t('views.IndexView.moduleEthnographyDesc') },
  { title: t('views.IndexView.moduleCities'), to: '/cities', img: historicCitiesImg, desc: t('views.IndexView.moduleCitiesDesc') },
  { title: t('views.IndexView.moduleNature'), to: '/nature', img: natureImg, desc: t('views.IndexView.moduleNatureDesc') },
  { title: t('views.IndexView.moduleArcheology'), to: '/archeology', img: archeologyImg, desc: t('views.IndexView.moduleArcheologyDesc') },
  { title: t('views.IndexView.moduleArchitecture'), to: '/architecture', img: architectureKirovImg, desc: t('views.IndexView.moduleArchitectureDesc') },
]

const crafts = [
  { title: t('views.IndexView.craftDymkovo'), img: craftDymkovo, desc: t('views.IndexView.craftDymkovoDesc') },
  { title: t('views.IndexView.craftKapokoren'), img: craftKapokoren, desc: t('views.IndexView.craftKapokorenDesc') },
  { title: t('views.IndexView.craftLace'), img: craftKruzhevo, desc: t('views.IndexView.craftLaceDesc') },
]

const currentCraft = computed(() => crafts[craftIndex.value])

function prevCraft() { craftIndex.value = (craftIndex.value + crafts.length - 1) % crafts.length }
function nextCraft() { craftIndex.value = (craftIndex.value + 1) % crafts.length }
</script>

<template>
  <div class="page">
    <a href="#modules" class="skip-link">{{ t('views.IndexView.skipLink') }}</a>

    <section class="hero">
      <div class="hero-bg" :style="{ backgroundImage: `url(${heroImg})`, transform: `translateY(${heroOffset}px)` }" />
      <div class="hero-overlay" />
      <div class="hero-content" :style="fadeStyle(0)">
        <p class="hero-eyebrow">{{ t('views.IndexView.heroEyebrow') }}</p>
        <h1 class="hero-title">{{ t('views.IndexView.heroTitle') }}</h1>
        <p class="hero-subtitle">{{ t('views.IndexView.heroSubtitle') }}</p>
        <a href="#modules" class="cta-btn">{{ t('views.IndexView.heroCta') }}</a>
      </div>
      <div class="hero-arrow">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E8DFC8" stroke-width="2" stroke-linecap="round">
          <line x1="12" y1="4" x2="12" y2="16" /><polyline points="6 11 12 17 18 11" />
        </svg>
      </div>
    </section>

    <section id="modules" class="modules" :style="fadeStyle(1)">
      <h2 class="section-heading">{{ t('views.IndexView.modulesHeading') }}</h2>
      <p class="section-sub">{{ t('views.IndexView.modulesSub') }}</p>
      <div class="modules-grid">
        <RouterLink v-for="m in modules" :key="m.to" :to="m.to" class="module-card-wrap">
          <div class="module-card" :class="{ 'module-card--recommended': m.recommended }">
            <div class="module-bg" :style="{ backgroundImage: `url(${m.img})` }" />
            <div class="module-overlay" />
            <div class="module-text">
              <h3 class="module-title">{{ m.title }}</h3>
              <p class="module-desc">{{ m.desc }}</p>
            </div>
          </div>
        </RouterLink>
      </div>
    </section>

    <section class="history" :style="fadeStyle(2)">
      <div class="history-inner">
        <div class="history-text">
          <p class="section-label">{{ t('views.IndexView.historyLabel') }}</p>
          <h2 class="section-heading">{{ t('views.IndexView.historyHeading') }}</h2>
          <p class="history-p">{{ t('views.IndexView.historyP1') }}</p>
          <p class="history-p">{{ t('views.IndexView.historyP2') }}</p>
          <p class="history-p">{{ t('views.IndexView.historyP3') }}</p>
          <RouterLink to="/cities" class="cta-btn">{{ t('views.IndexView.historyCta') }}</RouterLink>
        </div>
        <KremlinVideo />
      </div>

      <div class="tl">
        <div class="tl-line" />
        <RouterLink v-for="t in timelineItems" :key="t.id" :to="`/cities/kirov/event/${t.id}`" class="tl-item">
          <div class="tl-dot" />
          <span class="tl-year">{{ t.year }}</span>
          <span class="tl-text">{{ t.title }}</span>
        </RouterLink>
      </div>
    </section>

    <section class="crafts" :style="fadeStyle(3)">
      <div class="crafts-heading">
        <p class="section-label">{{ t('views.IndexView.craftsLabel') }}</p>
        <h2 class="section-heading">{{ t('views.IndexView.craftsHeading') }}</h2>
      </div>
      <div class="craft-card">
        <div class="craft-img" :style="{ backgroundImage: `url(${currentCraft.img})` }" />
        <div class="craft-body">
          <h3 class="craft-title">{{ currentCraft.title }}</h3>
          <p class="craft-desc">{{ currentCraft.desc }}</p>
          <RouterLink to="/ethnography" class="craft-link">{{ t('views.IndexView.craftMore') }}</RouterLink>
        </div>
      </div>
      <div class="craft-nav">
        <button :aria-label="t('views.IndexView.craftPrevAria')" class="craft-arrow" @click="prevCraft">‹</button>
        <button v-for="(c, i) in crafts" :key="c.title" :aria-label="c.title" class="craft-dot" :class="{ active: i === craftIndex }" @click="craftIndex = i" />
        <button :aria-label="t('views.IndexView.craftNextAria')" class="craft-arrow" @click="nextCraft">›</button>
      </div>
    </section>

    <section class="parallax" :style="fadeStyle(4)" id="archParallax">
      <div class="parallax-inner">
      <div class="parallax-bg" :style="{ backgroundImage: `url(${archImg})`, transform: `translateY(${archOffset}px)` }" />
      <div class="parallax-overlay" />
      <div class="parallax-card">
        <h2 class="parallax-title">{{ t('views.IndexView.parallaxTitle') }}</h2>
        <p class="parallax-text">{{ t('views.IndexView.parallaxText') }}</p>
        <RouterLink to="/architecture" class="cta-btn">{{ t('views.IndexView.parallaxCta') }}</RouterLink>
      </div>
      </div>
    </section>

    <section class="quote" :style="fadeStyle(5)">
      <blockquote class="quote-text">
        <span class="quote-mark">&#8220;</span>{{ t('views.IndexView.quoteText') }}
      </blockquote>
      <cite class="quote-cite">{{ t('views.IndexView.quoteCite') }}</cite>
    </section>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body); color: var(--color-ink); background: var(--color-bg) }
.skip-link { position: absolute; top: -100px; left: var(--space-1); z-index: 9999; background: var(--color-bg); color: var(--color-ink); padding: var(--space-1) var(--space-2); border-radius: var(--radius); font-size: 14px; transition: top 200ms }
.skip-link:focus { top: var(--space-1) }

.hero { position: relative; height: 100vh; min-height: 600px; overflow: hidden; display: flex; align-items: center; justify-content: center; margin-top: -80px; padding-top: 80px }
.hero-bg { position: absolute; inset: -10%; background-size: cover; background-position: center; filter: sepia(15%) brightness(0.9); z-index: 0; will-change: transform }
.hero-overlay { position: absolute; inset: 0; z-index: 1; background: linear-gradient(to bottom, rgba(60,122,140,0.35), rgba(42,33,24,0.25)) }
.hero-content { position: relative; z-index: 2; text-align: center; max-width: 800px; padding: var(--space-3) }
.hero-eyebrow { font-size: 15px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-oak); margin: 0 0 18px; text-shadow: 0 2px 8px rgba(0,0,0,0.6) }
.hero-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(38px,5vw,64px); line-height: 1.1; color: var(--color-bg); margin: 0 0 var(--space-2); text-shadow: 0 2px 12px rgba(0,0,0,0.45) }
.hero-subtitle { font-size: clamp(17px,1.8vw,22px); color: var(--color-oak); max-width: 640px; margin: 0 auto var(--space-3); text-shadow: 0 2px 10px rgba(0,0,0,0.5) }
.hero-arrow { position: absolute; bottom: var(--space-3); left: 50%; transform: translateX(-50%); z-index: 2; animation: pulse 2s infinite }
@keyframes pulse { 0%,100% { transform: translateX(-50%) translateY(0) } 50% { transform: translateX(-50%) translateY(6px) } }

.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 14px; font-weight: 500;
  padding: 14px 32px; background: var(--color-ochre); color: var(--color-bg);
  text-decoration: none; white-space: nowrap; min-height: 44px;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }

.section-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); text-align: center; margin: 0 0 var(--space-2) }
.section-sub { text-align: center; font-size: 16px; line-height: 1.65; color: var(--color-teal); max-width: 720px; margin: 0 auto var(--space-4) }
.section-label { color: var(--color-ochre-dark); font-size: 13px; letter-spacing: 0.15em; text-transform: uppercase; margin: 0 0 var(--space-1) }

.modules { background: var(--color-bg); padding: var(--space-6) var(--space-3); max-width: 1200px; margin: 0 auto }
.modules-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: var(--space-3) }
.module-card-wrap { text-decoration: none }
.module-card { position: relative; min-height: 280px; overflow: hidden; border-radius: var(--radius); transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out) }
.module-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg) }
.module-card--recommended { box-shadow: 0 4px 16px rgba(42,33,24,0.15) }
.module-card--recommended:hover { transform: translateY(-6px); box-shadow: 0 12px 32px rgba(42,33,24,0.18) }
.module-bg { position: absolute; inset: 0; background-size: cover; background-position: center }
.module-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(42,33,24,0.7), rgba(42,33,24,0.3) 55%, rgba(60,122,140,0.1)) }
.module-text { position: absolute; bottom: 0; padding: var(--space-3); color: var(--color-bg); width: 100% }
.module-title { color: var(--color-bg); margin: 0 0 4px; font-size: 20px; font-family: var(--font-display); font-weight: 700 }
.module-desc { font-size: 13px; line-height: 1.5; margin: 0 0 var(--space-1); opacity: 0.85 }

.history { padding: var(--space-6) var(--space-3); max-width: 1200px; margin: 0 auto; background: var(--color-birch) }
.history-inner { display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-4); align-items: center; margin-bottom: 48px }
@media (max-width: 767px) { .history-inner { grid-template-columns: 1fr; gap: var(--space-3) } }
.history-text { text-align: center }
.history-p { margin-bottom: 1em; line-height: 1.75 }
.history-p:last-of-type { margin-bottom: 1.5em }

.tl { position: relative; display: flex; justify-content: space-between; padding: var(--space-5) 0 0 }
.tl-line { position: absolute; left: 0; right: 0; top: 64px; height: 2px; background: var(--color-ochre) }
.tl-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; text-align: center; text-decoration: none; color: inherit }
.tl-dot { width: 16px; height: 16px; background: var(--color-ochre); border-radius: 50%; border: 3px solid var(--color-birch); box-shadow: 0 0 0 2px var(--color-ochre); position: absolute; top: 0; transform: translateY(-50%); touch-action: manipulation; transition: transform 200ms }
.tl-item:hover .tl-dot { transform: translateY(-50%) scale(1.2) }
.tl-item:hover .tl-text { color: var(--color-teal) }
.tl-year { position: absolute; top: -40px; left: 50%; transform: translateX(-50%); font-family: var(--font-display); font-weight: 700; font-size: 20px; color: var(--color-ochre-dark); white-space: nowrap }
.tl-text { font-size: 13px; color: var(--color-muted); max-width: 120px; line-height: 1.4; margin-top: var(--space-3) }
@media (max-width: 767px) {
  .tl { display: block; padding: var(--space-2) 0 }
  .tl-line { left: 50%; right: auto; top: 0; bottom: 0; height: auto; width: 2px; transform: translateX(-50%) }
  .tl-item {
    display: grid; grid-template-columns: 1fr 24px 1fr; align-items: center;
    column-gap: var(--space-2); padding: var(--space-3) 0; flex: none; text-align: initial;
  }
  .tl-dot { grid-column: 2; justify-self: center; position: relative; top: auto; transform: none; z-index: 1 }
  .tl-year { grid-column: 1; position: static; transform: none; text-align: right; white-space: normal }
  .tl-text { grid-column: 3; text-align: left; max-width: none; margin-top: 0 }
}

.crafts { background: var(--color-bg); padding: var(--space-6) var(--space-3); position: relative }
.crafts-heading { text-align: center; margin-bottom: var(--space-4) }
.craft-card { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); max-width: 1200px; margin: 0 auto; align-items: center }
@media (max-width: 767px) { .craft-card { grid-template-columns: 1fr } }
.craft-img { width: 100%; aspect-ratio: 4/3; background-size: cover; background-position: center; border-radius: var(--radius); border: 1.5px dashed var(--color-border); filter: sepia(20%) }
.craft-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(20px,2vw,26px); margin: 0 0 var(--space-2) }
.craft-desc { font-size: 14px; color: var(--color-muted); line-height: 1.7; margin: 0 0 var(--space-2) }
.craft-link { font-size: 13px; color: var(--color-ochre-dark); text-decoration: underline; text-underline-offset: 3px }
.craft-nav { display: flex; align-items: center; justify-content: center; gap: var(--space-2); margin-top: var(--space-3) }
.craft-arrow { background: transparent; border: 1px solid var(--color-border); color: var(--color-ochre-dark); width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 18px }
.craft-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--color-border); opacity: 0.4; border: none; cursor: pointer; padding: 0; transition: background 150ms, transform 150ms }
.craft-dot.active { background: var(--color-ochre); opacity: 1; transform: scale(1.25) }

.parallax { background: var(--color-birch); padding: var(--space-6) var(--space-3) }
.parallax-inner { position: relative; height: 70vh; min-height: 500px; overflow: hidden; display: flex; align-items: center; justify-content: center; border-radius: var(--radius); max-width: 1200px; margin: 0 auto }
.parallax-bg { position: absolute; inset: -10%; background-size: cover; background-position: center 40%; filter: brightness(0.8) saturate(0.9); z-index: 0; will-change: transform }
.parallax-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(42,33,24,0.35), rgba(60,122,140,0.25)); z-index: 1 }
.parallax-card { position: relative; z-index: 2; text-align: center; max-width: 640px; padding: var(--space-4); background: rgba(253,251,247,0.85); backdrop-filter: blur(8px); border-radius: var(--radius); border: 1.5px dashed var(--color-border) }
.parallax-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(22px,2.5vw,30px); margin: 0 0 var(--space-2) }
.parallax-text { color: var(--color-muted); font-size: 15px; margin: 0 0 var(--space-3); line-height: 1.6 }

.quote { background: var(--color-birch); padding: var(--space-6) var(--space-3); text-align: center }
.quote-text { font-family: var(--font-display); font-style: italic; font-size: clamp(18px,1.8vw,22px); color: var(--color-ink); max-width: 720px; margin: 0 auto; line-height: 1.6 }
.quote-mark { font-size: 1.5em; color: var(--color-ochre); opacity: 0.4; margin-right: 4px }
.quote-cite { display: block; font-style: normal; font-size: 13px; color: var(--color-muted); margin-top: var(--space-2) }
</style>
