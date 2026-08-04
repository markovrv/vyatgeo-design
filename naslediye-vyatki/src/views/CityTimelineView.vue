<script setup>
import { ref } from 'vue'
import cityHero from '@/assets/img/kremlin.jpeg'
import historicCitiesImg from '@/assets/img/historic-cities.jpg'
import architectureKirovImg from '@/assets/img/architecture-kirov.jpg'
import victoryHeroImg from '@/assets/img/victory-hero.jpg'

const stats = [
  { n: '1374', label: 'Первое упоминание в летописях' },
  { n: 'ок. 476 тыс.', label: 'Численность населения' },
  { n: '~170 км²', label: 'Площадь города' },
  { n: '3 города-побратима', label: 'Великий Устюг, Сыктывкар, Каунас' },
]

const timeline = [
  { year: '1374', text: 'Первое упоминание города Хлынова в летописях. Новгородские ушкуйники основали укреплённое поселение на высоком берегу реки Вятки.', color: '#C27E3A', img: cityHero, caption: 'Хлыновский кремль на высоком берегу Вятки' },
  { year: '1489', text: 'Вятская земля окончательно присоединена к Московскому княжеству Иваном III.', color: '#C27E3A', img: historicCitiesImg, caption: 'Присоединение Вятской земли к Москве' },
  { year: '1580', text: 'Монах Трифон Вятский основывает Успенский монастырь — первую каменную обитель Вятской земли.', color: '#3C7A8C', img: cityHero, caption: 'Успенский Трифонов монастырь' },
  { year: '1780', text: 'Хлынов переименован в Вятку указом Екатерины II. Город получает герб и регулярный план застройки.', color: '#C27E3A', img: architectureKirovImg, caption: 'Каменная застройка эпохи классицизма' },
  { year: '1796', text: 'Учреждение Вятской губернии. Вятка становится губернским центром.', color: '#C27E3A', img: architectureKirovImg, caption: 'Губернский центр Вятка' },
  { year: '1858', text: 'Открытие первой публичной библиотеки в Вятке. Город становится культурным центром губернии.', color: '#3C7A8C', img: historicCitiesImg, caption: 'Вид города Вятки' },
  { year: '1934', text: 'Город переименован в Киров в память о С.М. Кирове.', color: '#C27E3A', img: architectureKirovImg, caption: 'Город Киров, 1930-е' },
  { year: '1936', text: 'Образование Кировской области. Город становится областным центром.', color: '#C27E3A', img: historicCitiesImg, caption: 'Образование Кировской области' },
  { year: '1941–1945', text: 'В Киров эвакуированы десятки заводов. Город становится центром оборонной промышленности.', color: '#3C7A8C', img: victoryHeroImg, caption: 'Эвакуированные заводы и фронтовики-кировчане' },
  { year: '2026', text: 'Киров готовится к 90-летию Кировской области.', color: '#C27E3A', img: cityHero, caption: 'Киров к 90-летию области' },
]
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/cities">Исторические города</RouterLink><span>/</span>
      <span class="current">Киров</span>
    </nav>

    <section class="hero">
      <div class="hero-bg" :style="{ backgroundImage: `url(${cityHero})` }" />
      <div class="hero-overlay" />
      <div class="hero-content">
        <p class="hero-eyebrow">К 90-летию Кировской области · Исторические города</p>
        <h1 class="hero-title">Киров (Вятка / Хлынов)</h1>
        <p class="hero-subtitle">Областной центр · Основан в 1374 году · Ранее: Хлынов (до 1780), Вятка (1780–1934)</p>
      </div>
    </section>

    <section class="info">
      <div class="info-grid">
        <div>
          <h2 class="info-heading">Общие сведения</h2>
          <p>Киров — административный центр Кировской области, крупный промышленный, культурный и исторический центр Приволжского федерального округа. Расположен на реке Вятке, в 896 км к северо-востоку от Москвы.</p>
          <p>Город ведёт свою историю с 1374 года, когда на высоком берегу реки Вятки новгородские переселенцы основали укреплённое поселение Хлынов. В 1780 году по указу Екатерины II городу было присвоено имя Вятка, а в 1934 году он был переименован в Киров.</p>
          <RouterLink to="/cities/kirov/event" class="cta-btn">Лента времени</RouterLink>
        </div>
        <div class="stats-col">
          <div v-for="s in stats" :key="s.label" class="stat">
            <strong class="stat-n">{{ s.n }}</strong>
            <span class="stat-label">{{ s.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="timeline-section">
      <h2 class="section-heading">Лента времени</h2>
      <p class="section-sub">Ключевые события в истории города от первого упоминания до наших дней</p>
      <div class="timeline">
        <div class="tl-line" />
        <div v-for="(t, i) in timeline" :key="i" class="tl-item">
          <div class="tl-dot" :style="{ background: t.color, boxShadow: `0 0 0 2px ${t.color}` }" />
          <div class="tl-year">{{ t.year }}</div>
          <div class="tl-text">{{ t.text }}</div>
          <RouterLink :to="`/cities/kirov/event`" class="tl-img-link">
            <div class="tl-img" :style="{ backgroundImage: `url(${t.img})` }" :aria-label="t.caption" />
            <div class="tl-caption">{{ t.caption }}</div>
          </RouterLink>
        </div>
      </div>
    </section>

    <div class="back-bar">
      <RouterLink to="/cities" class="cta-btn">← К списку городов</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.breadcrumb { max-width: 1000px; margin: 0 auto; padding: var(--space-2) var(--space-3) 0; display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; font-size: 13px; color: var(--color-muted) }
.breadcrumb a { color: var(--color-ochre); text-decoration: none }
.current { color: var(--color-ink) }

.hero { position: relative; height: 60vh; min-height: 560px; overflow: hidden; display: flex; align-items: center; justify-content: center; margin-top: -80px; padding-top: 80px }
.hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; filter: sepia(50%) brightness(0.8); z-index: 0 }
.hero-overlay { position: absolute; inset: 0; z-index: 1; background: linear-gradient(to bottom, rgba(60,122,140,0.35), rgba(42,33,24,0.25)) }
.hero-content { position: relative; z-index: 2; text-align: center; max-width: 800px; padding: var(--space-4) var(--space-3) }
.hero-eyebrow { font-size: 13px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: var(--color-oak); margin: 0 0 var(--space-2); text-shadow: 0 2px 8px rgba(0,0,0,0.6) }
.hero-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(32px,4vw,52px); color: var(--color-bg); margin: 0 0 var(--space-2); text-shadow: 0 2px 12px rgba(0,0,0,0.45) }
.hero-subtitle { font-size: clamp(15px,1.4vw,18px); color: var(--color-oak); max-width: 600px; margin: 0 auto; text-shadow: 0 2px 10px rgba(0,0,0,0.5) }

.info { padding: var(--space-5) var(--space-3); max-width: 1200px; margin: 0 auto }
.info p { line-height: 1.8; margin-bottom: 1em }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); align-items: start }
.info-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(22px,2.5vw,28px); margin: 0 0 var(--space-2) }
.stats-col { display: flex; flex-direction: column; gap: var(--space-2) }
.stat { border-left: 3px solid var(--color-ochre); padding-left: var(--space-2) }
.stat-n { display: block; font-size: 28px; font-family: var(--font-display); color: var(--color-teal); line-height: 1.2 }
.stat-label { font-size: 14px; color: var(--color-muted) }

.timeline-section { background: var(--color-birch); padding: var(--space-5) var(--space-3) }
.section-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); text-align: center; margin: 0 0 var(--space-2) }
.section-sub { text-align: center; font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-4) }
.timeline { position: relative; max-width: 800px; margin: 0 auto }
.tl-line { position: absolute; left: 19px; top: 0; bottom: 0; width: 2px; background: var(--color-ochre) }
.tl-item { position: relative; padding-left: 56px; margin-bottom: 32px }
.tl-dot { position: absolute; left: 12px; top: 6px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid var(--color-bg) }
.tl-year { font-family: var(--font-display); font-weight: 700; font-size: 22px; color: var(--color-ochre) }
.tl-text { font-size: 15px; margin-top: 4px; line-height: 1.6 }
.tl-img-link { display: block; margin-top: 8px; border-radius: var(--radius); overflow: hidden; max-width: 400px; border: 1.5px dashed var(--color-border); text-decoration: none; transition: border-color 300ms, box-shadow 300ms }
.tl-img-link:hover { border-color: var(--color-ochre); box-shadow: 0 4px 16px rgba(194,126,58,0.25) }
.tl-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center; filter: sepia(20%) }
.tl-caption { padding: 6px 10px; font-size: 12px; color: var(--color-muted); text-align: center; background: var(--color-surface) }

.back-bar { padding: var(--space-3); max-width: 1200px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }

.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface);
  text-decoration: none; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }
</style>
