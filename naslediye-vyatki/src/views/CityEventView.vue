<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import image from '@/assets/img/kremlin.jpeg'

const evt = ref({
  year: '1580',
  img: image,
  caption: 'Монах Трифон Вятский основывает Успенский монастырь',
  fullText: 'В 1580 году преподобный Трифон Вятский основал на берегу реки Вятки мужской Успенский монастырь — первую каменную обитель Вятской земли. Монастырь быстро стал духовным и культурным центром всего края, вокруг него формировалась городская застройка Хлынова. Трифон лично руководил строительством первого каменного Успенского собора, заложив традицию каменного зодчества на Вятке. Монастырь пережил Смутное время, пожары и перестройки, но сохранил своё значение до наших дней как главный архитектурный ансамбль города.',
  era: 'Средневековье',
  location: 'г. Киров, ул. Горбачева, 4',
})

const stats = [
  { n: evt.value.year, label: 'год события' },
  { n: evt.value.era, label: 'эпоха' },
  { n: evt.value.location, label: 'место' },
]

const nearby = [
  { year: '1489', label: 'Присоединение к Москве', active: false, alt: false },
  { year: '1580', label: 'Трифонов монастырь', active: true, alt: false },
  { year: '1780', label: 'Переименован в Вятку', active: false, alt: false },
  { year: '1796', label: 'Вятская губерния', active: false, alt: false },
  { year: '1858', label: 'Публичная библиотека', active: false, alt: true },
]
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Все разделы</RouterLink><span>/</span>
      <RouterLink to="/cities">Исторические города</RouterLink><span>/</span>
      <RouterLink to="/cities/kirov">Киров</RouterLink><span>/</span>
      <span class="current">Событие {{ evt.year }} года</span>
    </nav>

    <section class="intro">
      <div class="frame">
        <img :src="evt.img" :alt="evt.caption" />
      </div>
      <h1 class="title">Событие {{ evt.year }} года</h1>
      <p class="meta">{{ evt.caption }}</p>
    </section>

    <section class="section">
      <h2 class="heading">Исторический контекст</h2>
      <p class="text">{{ evt.fullText }}</p>

      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <div class="back-bar">
      <RouterLink to="/cities/kirov" class="cta-btn">← К ленте времени Кирова</RouterLink>
    </div>

    <section class="nearby">
      <h2 class="nearby-heading">События в истории Кирова</h2>
      <p class="nearby-sub">Навигация по ленте времени — выберите событие</p>
      <div class="ctln">
        <div class="ctln-line" />
        <RouterLink
          v-for="n in nearby"
          :key="n.year"
          :to="'/cities/kirov/event'"
          class="ctln-item"
        >
          <div class="ctln-dot" :class="{ 'ctln-dot--active': n.active, 'ctln-dot--alt': n.alt }" />
          <span class="ctln-year" :class="{ 'ctln-year--active': n.active, 'ctln-year--alt': n.alt }">{{ n.year }}</span>
          <span class="ctln-label" :class="{ 'ctln-label--active': n.active }">{{ n.label }}</span>
        </RouterLink>
      </div>
      <div class="nearby-nav">
        <RouterLink to="/cities/kirov/event" class="cta-btn">← Ранее</RouterLink>
        <RouterLink to="/cities/kirov/event" class="cta-btn">Позднее →</RouterLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.breadcrumb { max-width: 1000px; margin: 0 auto; padding: var(--space-2) var(--space-3) 0; display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; font-size: 13px; color: var(--color-muted) }
.breadcrumb a { color: var(--color-ochre); text-decoration: none }
.current { color: var(--color-ink) }

.intro { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) 0 }
.frame { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-surface); margin-bottom: var(--space-3) }
.frame img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; filter: sepia(20%) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin-bottom: var(--space-3) }

.section { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3) var(--space-5) }
.heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 12px }
.text { line-height: 1.75; margin-bottom: 1em }

.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); margin-top: var(--space-4) }
.stat { text-align: center; padding: var(--space-2); border: 1.5px dashed var(--color-border); border-radius: var(--radius); background: var(--color-surface) }
.stat-n { display: block; font-family: var(--font-display); font-size: 24px; color: var(--color-teal) }
.stat-label { font-size: 13px; color: var(--color-muted) }

.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }

.nearby { background: var(--color-birch); padding: var(--space-5) var(--space-3) var(--space-6) }
.nearby-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(22px,2.5vw,28px); text-align: center; margin: 0 0 var(--space-1) }
.nearby-sub { text-align: center; font-size: 14px; color: var(--color-muted); margin: 0 0 var(--space-5) }

.ctln { position: relative; display: flex; justify-content: space-between; max-width: 600px; margin: 0 auto; padding: 48px 0 0 }
.ctln-line { position: absolute; left: 0; right: 0; top: 48px; height: 2px; background: var(--color-ochre) }
.ctln-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; text-align: center; text-decoration: none }
.ctln-dot { position: absolute; top: 0; transform: translateY(-50%); width: 16px; height: 16px; background: var(--color-ochre); border-radius: 50%; border: 3px solid var(--color-birch); box-shadow: 0 0 0 2px var(--color-ochre); touch-action: manipulation; flex-shrink: 0; transition: transform 200ms }
.ctln-item:hover .ctln-dot { transform: translateY(-50%) scale(1.25) }
.ctln-dot--active { width: 18px; height: 18px }
.ctln-dot--alt { background: var(--color-teal); box-shadow: 0 0 0 2px var(--color-teal) }
.ctln-year { position: absolute; top: -32px; left: 50%; transform: translateX(-50%); font-family: var(--font-display); font-weight: 700; font-size: 20px; color: var(--color-ochre); white-space: nowrap }
.ctln-year--active { font-size: 22px }
.ctln-year--alt { color: var(--color-teal) }
.ctln-label { font-size: 12px; color: var(--color-muted); max-width: 80px; line-height: 1.3; margin-top: 20px }
.ctln-label--active { color: var(--color-ochre) }

.nearby-nav { display: flex; justify-content: center; gap: var(--space-2); margin-top: var(--space-5) }

.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface);
  text-decoration: none; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }

@media (max-width: 767px) {
  .ctln { flex-direction: column; align-items: flex-start; gap: var(--space-3) }
  .ctln-line { display: none }
  .ctln-dot { width: 24px; height: 24px; position: relative; top: auto; transform: none; flex-shrink: 0 }
  .ctln-item:hover .ctln-dot { transform: none }
  .ctln-item { display: flex; gap: var(--space-2); align-items: flex-start }
  .ctln-year { position: relative; top: auto; left: auto; transform: none; white-space: normal }
  .ctln-label { margin-top: 0 }
  .stats { grid-template-columns: 1fr }
}
</style>
