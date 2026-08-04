<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import image from '@/assets/img/dymkovo-toys.jpg'

const item = ref({
  name: 'Дымковская игрушка',
  origin: 'слобода Дымково, г. Вятка · XVII–XX вв.',
  description: 'Глиняная расписная фигурка — один из старейших русских народных промыслов, зародившийся в слободе Дымково на окраине Вятки. Яркие геометрические узоры — круги, клетки, полоски — делают дымковскую игрушку узнаваемым символом Вятского края.',
})

const stats = [
  { n: 'Русские', label: 'этнос' },
  { n: 'XVII–XX вв.', label: 'период бытования' },
  { n: 'Глина, темпера', label: 'материалы' },
]

const traditions = [
  'Промысел возник в слободе Дымково, расположенной на левом берегу реки Вятки напротив Хлынова, предположительно в XVII веке. По легенде, изготовление глиняных свистулек было связано с ежегодным весенним праздником «Свистунья», во время которого горожане поминали погибших при защите города.',
  'Промысел передавался по женской линии — от матери к дочери. К концу XIX века дымковская игрушка получила широкую известность за пределами губернии и стала одним из символов русского народного искусства.',
]

const technology = 'Игрушки лепятся из местной красной глины, затем обжигаются и покрываются меловым грунтом, после чего расписываются темперными красками с добавлением поталевого золота. Сюжеты традиционны: барыни, всадники, животные, сказочные персонажи.'
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/ethnography">Этнографическое наследие</RouterLink><span>/</span>
      <span class="current">Дымковская игрушка</span>
    </nav>

    <section class="intro">
      <div class="item-grid">
        <div class="frame">
          <img :src="image" alt="Дымковская игрушка" />
        </div>
        <div class="item-content">
          <div class="tags">
            <span class="tag tag--teal">Русские</span>
            <span class="tag tag--ochre">Промыслы</span>
          </div>
          <h1 class="title">{{ item.name }}</h1>
          <p class="meta">{{ item.origin }}</p>
          <p class="desc">{{ item.description }}</p>
        </div>
      </div>
    </section>

    <section class="section">
      <h2 class="heading">Место и время бытования</h2>
      <p v-for="(p, i) in traditions" :key="i" class="text">{{ p }}</p>

      <h2 class="heading heading--offset">Этнос и материалы</h2>
      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
      <p class="text">{{ technology }}</p>
    </section>

    <div class="back-bar">
      <RouterLink to="/ethnography" class="cta-btn">← К каталогу экспонатов</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.breadcrumb { max-width: 1000px; margin: 0 auto; padding: var(--space-2) var(--space-3) 0; display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; font-size: 13px; color: var(--color-muted) }
.breadcrumb a { color: var(--color-ochre); text-decoration: none }
.current { color: var(--color-ink) }

.intro { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5) }
.item-grid { display: grid; grid-template-columns: 280px 1fr; gap: var(--space-4); align-items: start }
.frame { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-surface) }
.frame img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block }
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap; margin-bottom: 12px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin-bottom: var(--space-2) }
.desc { line-height: 1.75 }

.section { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3) var(--space-5) }
.heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 12px }
.heading--offset { margin-top: var(--space-4) }
.text { line-height: 1.75; margin-bottom: 1em }

.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); margin-bottom: var(--space-3) }
.stat { text-align: center; padding: var(--space-2); border: 1.5px dashed var(--color-border); border-radius: var(--radius); background: var(--color-surface) }
.stat-n { display: block; font-family: var(--font-display); font-size: 24px; color: var(--color-teal) }
.stat-label { font-size: 13px; color: var(--color-muted) }

.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }

.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface);
  text-decoration: none; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }

@media (max-width: 1023px) {
  .item-grid { grid-template-columns: 240px 1fr; gap: var(--space-3) }
}
@media (max-width: 767px) {
  .item-grid { grid-template-columns: 1fr }
  .stats { grid-template-columns: 1fr }
}
</style>
