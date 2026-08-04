<script setup>
import aboutImage from '@/assets/img/kremlin.jpeg'

const stats1 = [
  { n: '6', label: 'тематических модулей' },
  { n: '10+', label: 'страниц контента' },
  { n: 'более 70', label: 'описанных объектов' },
]
const stats2 = [
  { n: '~150', label: 'памятников археологии' },
  { n: 'более 100', label: 'памятников природы' },
  { n: '5', label: 'народов региона' },
]
const modules = [
  { title: 'Лица Победы', to: '/victory', desc: 'Живая летопись ратной славы Вятской земли с XIV века до наших дней.' },
  { title: 'Исторические города', to: '/cities', desc: 'Ленты времени семи городов: Кирова, Котельнича, Орлова, Слободского, Яранска, Малмыжа и Уржума.' },
  { title: 'Памятники археологии', to: '/archeology', desc: 'Около 150 памятников от эпохи мезолита до позднего Средневековья.' },
  { title: 'Этнографическое наследие', to: '/ethnography', desc: 'Коллекция предметов быта, костюмов и обрядов народов Вятского края.' },
  { title: 'Архитектура Кирова', to: '/architecture', desc: 'Цифровой архив зданий и памятников областного центра.' },
  { title: 'Памятники природы', to: '/nature', desc: 'Более 100 уникальных природных объектов региона.' },
  { title: 'Археология Кирова', to: '/archeology', desc: 'Раскопки в историческом центре Кирова, открывающие следы средневекового Хлынова.' },
]
</script>

<template>
  <div class="about-page">
    <section class="hero">
      <div class="hero-bg" :style="{ backgroundImage: `url(${aboutImage})` }" />
      <div class="hero-overlay" />
      <div class="hero-panel">
        <p class="hero-eyebrow">К 90-летию Кировской области · 1936–2026</p>
        <h1 class="hero-title">О проекте</h1>
      </div>
    </section>

    <section class="intro">
      <h2 class="section-heading">Историко-культурное наследие<br />Вятского края</h2>
      <p><strong>«Наследие Вятки»</strong> — просветительский проект, приуроченный к 90-летию образования Кировской области (5 декабря 1936 — 5 декабря 2026). Его цель — собрать, сохранить и сделать доступными для широкой аудитории уникальные материалы по истории, культуре и природе Вятской земли.</p>
      <p>Вятский край — один из самобытнейших регионов России, где на протяжении столетий переплетались культурные традиции русских, татар, удмуртов, марийцев и коми. Здесь формировался уникальный архитектурный облик городов, возникли прославленные народные промыслы — дымковская игрушка, вятское кружево, изделия из капокорня.</p>
      <p>Проект объединяет шесть тематических модулей, каждый из которых посвящён одному из аспектов наследия региона: от средневековой археологии до современной архитектуры, от воинской славы до этнографического многообразия.</p>
    </section>

    <section class="stats-section">
      <h2 class="section-heading">Проект в цифрах</h2>
      <div class="stats-grid">
        <div v-for="s in stats1" :key="s.label" class="stat-card">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
      <div class="stats-grid">
        <div v-for="s in stats2" :key="s.label" class="stat-card">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <section class="modules-section">
      <h2 class="section-title">Модули проекта</h2>
      <div class="modules-grid">
        <RouterLink v-for="m in modules" :key="m.to" :to="m.to" class="module-card">
          <h3 class="module-title">{{ m.title }}</h3>
          <p class="module-desc">{{ m.desc }}</p>
        </RouterLink>
      </div>
    </section>

    <section class="science">
      <h2 class="section-title">Научная основа</h2>
      <p>Материалы проекта базируются на многолетних исследованиях:</p>
      <ul>
        <li>Археологические экспедиции Кировского государственного педагогического института им. В.И. Ленина</li>
        <li>Этнографические экспедиции Вятского государственного гуманитарного университета</li>
        <li>Раскопки Кировского областного краеведческого музея</li>
        <li>Работы архитекторов и краеведов Кировской организации Союза архитекторов России</li>
        <li>Исследования природных заказников и памятников природы регионального значения</li>
      </ul>

      <h2 class="section-title">Партнёры и поддержка</h2>
      <p>Проект реализован при поддержке:</p>
      <ul>
        <li>Правительства Кировской области</li>
        <li>Кировского областного краеведческого музея</li>
        <li>Вятского государственного университета</li>
        <li>Кировской организации Союза архитекторов России</li>
      </ul>

      <h3 class="help-heading">Как помочь проекту</h3>
      <p>Если у вас есть фотографии, документы, письма или предметы быта, связанные с историей Вятского края, — пришлите их нам для оцифровки и публикации.</p>
    </section>

    <section class="cta">
      <h2 class="section-heading">Сохраним наследие вместе</h2>
      <p class="cta-sub">Проект «Наследие Вятки» — это живой архив, который будет пополняться новыми материалами</p>
      <div class="cta-row">
        <a href="mailto:info@vyatka-heritage.ru" class="cta-btn">Написать нам</a>
        <span class="cta-email">info@vyatka-heritage.ru</span>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-page { font-family: var(--font-body); }

.hero {
  position: relative; height: 60vh; min-height: 560px; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  margin-top: -80px; padding-top: 80px;
}
.hero-bg {
  position: absolute; inset: 0; background-size: cover; background-position: center;
  filter: sepia(50%) brightness(0.8); z-index: 0;
}
.hero-overlay {
  position: absolute; inset: 0; z-index: 1;
  background: linear-gradient(to bottom, rgba(60,122,140,0.35), rgba(42,33,24,0.25));
}
.hero-panel {
  position: relative; z-index: 2; text-align: center; max-width: 700px;
  padding: 20px 28px; background: rgba(42,33,24,0.4);
  backdrop-filter: blur(6px); border-radius: var(--radius-lg); margin-top: 32px;
}
.hero-eyebrow {
  font-size: 13px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase;
  color: var(--color-oak); margin: 0 0 var(--space-2);
  text-shadow: 0 2px 8px rgba(42,33,24,0.6);
}
.hero-title {
  font-family: var(--font-display); font-weight: 700; font-size: clamp(32px,4vw,52px);
  color: var(--color-bg); margin: 0;
  text-shadow: 0 2px 12px rgba(42,33,24,0.45);
}

.intro { max-width: 1000px; margin: 0 auto; padding: var(--space-5) var(--space-3) }
.intro p { font-size: 16px; line-height: 1.8; margin-bottom: 1.2em }
.intro p:last-child { margin-bottom: 0 }

.section-heading {
  font-family: var(--font-display); font-weight: 700;
  font-size: clamp(26px,3vw,36px); text-align: center; margin: 0 0 var(--space-3);
}
.section-title {
  font-family: var(--font-display); font-weight: 700;
  font-size: clamp(22px,2.5vw,30px); margin: 0 0 var(--space-3);
}

.stats-section { background: var(--color-birch); padding: var(--space-5) var(--space-3) }
.stats-grid {
  display: grid; grid-template-columns: repeat(3,1fr); gap: var(--space-2);
  max-width: 1000px; margin: 0 auto var(--space-2);
}
.stats-grid:last-child { margin-bottom: 0 }
.stat-card {
  text-align: center; padding: var(--space-3);
  border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  background: var(--color-surface);
}
.stat-n { display: block; font-family: var(--font-display); font-size: 36px; color: var(--color-teal) }
.stat-label { font-size: 14px; color: var(--color-muted) }

.modules-section { max-width: 1000px; margin: 0 auto; padding: var(--space-5) var(--space-3) }
.modules-grid {
  display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: var(--space-3);
}
.module-card {
  display: block; padding: var(--space-2); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); background: var(--color-surface); text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.module-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(42,33,24,0.12) }
.module-title { font-size: 17px; font-weight: 700; margin: 0 0 4px; color: var(--color-ink); font-family: var(--font-display) }
.module-desc { font-size: 13px; color: var(--color-muted); line-height: 1.5; margin: 0 }

.science { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3) var(--space-5) }
.science p { line-height: 1.8; margin-bottom: 1em }
.science ul { margin: 0 0 1em var(--space-3); line-height: 1.8; padding: 0 }
.help-heading { margin-top: var(--space-3); margin-bottom: var(--space-1) }

.cta { background: var(--color-birch); padding: var(--space-5) var(--space-3); text-align: center }
.cta-sub { font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-2) }
.cta-row { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; align-items: center }
.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 14px; font-weight: 500;
  padding: 14px 32px; background: var(--color-ochre); color: var(--color-bg);
  text-decoration: none; min-height: 44px;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }
.cta-email { display: inline-flex; align-items: center; gap: 8px; padding: 8px 0; color: var(--color-muted) }
</style>
