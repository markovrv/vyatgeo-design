<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import image from '@/assets/img/kremlin.jpeg'

const site = ref({
  name: 'Хлыновский кремль',
  location: 'г. Киров, исторический центр · XII–XV вв.',
})

const stats = [
  { n: 'XII–XV вв.', label: 'датировка' },
  { n: '1950-е', label: 'начало раскопок' },
  { n: 'регионального значения', label: 'статус охраны' },
]

const landscape = 'Детинец средневекового Хлынова расположен на высоком мысу коренного берега реки Вятки, ограниченном с двух сторон оврагами. Естественная возвышенность и обрывистые склоны делали площадку труднодоступной для нападения, что определило выбор места для укреплённого поселения.'

const research = 'Систематические археологические раскопки на территории кремля начались в 1950-х годах и продолжаются периодически по сей день. Исследования выявили несколько строительных горизонтов, соответствующих разным этапам существования Хлынова.'

const findings = [
  'Раскопки выявили остатки деревянных укреплений, частокола и мостовых. Среди находок — фрагменты керамики, наконечники стрел, бытовые предметы, украшения и монеты XIV–XVII веков.',
  'Сегодня территория памятника частично застроена, культурный слой сохранился на глубине 1,5–3 м. Памятник имеет статус объекта культурного наследия регионального значения и находится под охраной государства.',
]
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/archeology">Памятники археологии</RouterLink><span>/</span>
      <span class="current">Хлыновский кремль</span>
    </nav>

    <section class="intro">
      <div class="frame">
        <img :src="image" alt="Хлыновский кремль" />
      </div>
      <div class="tags">
        <span class="tag tag--teal">Средневековье</span>
        <span class="tag tag--ochre">Городище</span>
      </div>
      <h1 class="title">{{ site.name }}</h1>
      <p class="meta">{{ site.location }}</p>
    </section>

    <section class="section">
      <div class="cols">
        <div>
          <h2 class="heading">Ландшафт</h2>
          <p class="text">{{ landscape }}</p>
        </div>
        <div>
          <h2 class="heading">История исследований</h2>
          <p class="text">{{ research }}</p>
        </div>
      </div>

      <h2 class="heading heading--offset">Находки и сохранность</h2>
      <p v-for="(p, i) in findings" :key="i" class="text">{{ p }}</p>

      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <div class="back-bar">
      <RouterLink to="/archeology" class="cta-btn">← К карте и каталогу</RouterLink>
    </div>
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
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap; margin-bottom: 12px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin-bottom: var(--space-3) }

.section { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3) var(--space-5) }
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4) }
.heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 12px }
.heading--offset { margin-top: var(--space-4) }
.text { line-height: 1.75; margin-bottom: 1em }

.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); margin-top: var(--space-4) }
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

@media (max-width: 767px) {
  .cols { grid-template-columns: 1fr }
  .stats { grid-template-columns: 1fr }
}
</style>
