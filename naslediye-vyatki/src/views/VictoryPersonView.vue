<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import image from '@/assets/img/victory-hero.jpg'

const person = ref({
  name: '311-я стрелковая дивизия',
  division: 'Великая Отечественная война · 1941–1945',
  years: '1941–1945',
  description: 'Сформированная в июле 1941 года в Кирове по мобилизационному плану, дивизия объединила уроженцев Кировской области — рабочих, колхозников, студентов вятских вузов. Прошла боевой путь от обороны Ленинграда до штурма Кёнигсберга в Восточной Пруссии.',
})

const stats = [
  { n: '1941', label: 'Год формирования' },
  { n: '~11 тыс.', label: 'Бойцов в составе' },
  { n: '3', label: 'Боевые награды' },
]

const bio = [
  'Дивизия была сформирована в первые недели войны на территории Кировской области. В её ряды вошли около 11 тысяч человек — призывники Кирова, Слободского, Котельнича и окружающих районов. Значительную часть личного состава составляли выпускники вятских педагогических и индустриальных техникумов.',
  'После краткой подготовки в учебных лагерях под Кировом дивизия была направлена на Северо-Западный фронт, где приняла первый бой в августе 1941 года на подступах к Новгороду.',
]

const path = [
  { date: 'Июль 1941', text: 'Формирование дивизии в Кирове по мобилизационному плану Кировской области.' },
  { date: 'Август 1941', text: 'Первые бои на Северо-Западном фронте под Новгородом.' },
  { date: '1941–1943', text: 'Оборонительные бои на Волховском фронте, участие в прорыве блокады Ленинграда.' },
  { date: '1944', text: 'Наступательные операции в Прибалтике, освобождение Псковской области.' },
  { date: '1945', text: 'Штурм Кёнигсберга в составе 3-го Белорусского фронта. Завершение боевого пути в Восточной Пруссии.' },
]

const awards = [
  { icon: '🎖️', title: 'Орден Красного Знамени', desc: 'Дивизия удостоена ордена за прорыв блокады Ленинграда в 1943 году.' },
  { icon: '⭐', title: 'Звание Героя Советского Союза', desc: '7 бойцов дивизии удостоены высшей награды за подвиги на фронте.' },
  { icon: '🏅', title: 'Медаль «За оборону Ленинграда»', desc: 'Награждены все военнослужащие, участвовавшие в обороне города.' },
]
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/victory">Лица Победы</RouterLink><span>/</span>
      <span class="current">311-я стрелковая дивизия</span>
    </nav>

    <section class="intro">
      <div class="person-grid">
        <div class="frame">
          <img :src="image" alt="Бойцы 311-й стрелковой дивизии" />
        </div>
        <div class="person-content">
          <p class="eyebrow">Великая Отечественная война · 1941–1945</p>
          <h1 class="title">{{ person.name }}</h1>
          <p class="desc">{{ person.description }}</p>
          <div class="tags">
            <span class="tag tag--teal">Стрелковая дивизия</span>
            <span class="tag tag--ochre">Сформирована в Кирове</span>
          </div>
        </div>
      </div>
      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <section class="section">
      <h2 class="heading">Биография формирования</h2>
      <p v-for="(p, i) in bio" :key="i" class="text">{{ p }}</p>

      <h2 class="heading heading--offset">Боевой путь</h2>
      <div class="timeline">
        <div class="tl-line" />
        <div v-for="(p, i) in path" :key="i" class="tl-item">
          <div class="tl-dot" />
          <strong class="tl-year">{{ p.date }}</strong>
          <p class="tl-text">{{ p.text }}</p>
        </div>
      </div>

      <h2 class="heading heading--offset">Наградные листы</h2>
      <div class="award-grid">
        <div v-for="a in awards" :key="a.title" class="award">
          <div class="award-icon">{{ a.icon }}</div>
          <h4 class="award-title">{{ a.title }}</h4>
          <p class="award-desc">{{ a.desc }}</p>
        </div>
      </div>
    </section>

    <div class="back-bar">
      <RouterLink to="/victory" class="cta-btn">← К списку Лиц Победы</RouterLink>
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
.person-grid { display: grid; grid-template-columns: 280px 1fr; gap: var(--space-4); align-items: start }
.frame { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-surface) }
.frame img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; filter: sepia(20%) }
.eyebrow { font-size: 13px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-teal); margin: 0 0 var(--space-1) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 var(--space-2) }
.desc { font-size: 16px; line-height: 1.75; margin: 0 0 var(--space-2) }
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }

.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); margin-top: var(--space-4) }
.stat { text-align: center; padding: var(--space-2); border: 1.5px dashed var(--color-border); border-radius: var(--radius); background: var(--color-surface) }
.stat-n { display: block; font-family: var(--font-display); font-size: 24px; color: var(--color-teal) }
.stat-label { font-size: 13px; color: var(--color-muted) }

.section { max-width: 1000px; margin: 0 auto; padding: var(--space-5) var(--space-3) }
.heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2) }
.heading--offset { margin-top: var(--space-4) }
.text { line-height: 1.8; margin-bottom: 1em }

.timeline { position: relative; max-width: 800px; margin: 0 auto; padding-left: var(--space-3); border-left: 2px solid var(--color-ochre) }
.tl-item { position: relative; margin-bottom: 20px }
.tl-dot { position: absolute; left: -30px; top: 4px; width: 16px; height: 16px; background: var(--color-ochre); border-radius: 50%; border: 3px solid var(--color-bg); box-shadow: 0 0 0 2px var(--color-ochre) }
.tl-year { font-family: var(--font-display); font-weight: 700; font-size: 20px; color: var(--color-ochre) }
.tl-text { margin-top: 4px; line-height: 1.6 }

.award-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: var(--space-2) }
.award { border: 1.5px dashed var(--color-border); border-radius: var(--radius); padding: var(--space-2); background: var(--color-surface) }
.award-icon { font-size: 24px; margin-bottom: var(--space-1) }
.award-title { font-size: 15px; font-weight: 700; margin: 0 0 4px }
.award-desc { font-size: 13px; color: var(--color-muted); line-height: 1.5 }

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
  .person-grid { grid-template-columns: 240px 1fr; gap: var(--space-3) }
}
@media (max-width: 767px) {
  .person-grid { grid-template-columns: 1fr }
  .stats { grid-template-columns: 1fr }
}
</style>
