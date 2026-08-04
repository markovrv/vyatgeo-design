<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const site = ref({
  name: 'Котельничское местонахождение парейазавров',
  location: 'Котельничский район, правый берег р. Вятки · Площадь 1 200 га',
})

const stats = [
  { n: '1 200 га', label: 'площадь' },
  { n: 'с 1930-х', label: 'ведутся раскопки' },
  { n: 'региональный', label: 'статус охраны' },
]

const origin = {
  head: 'Происхождение',
  paragraphs: [
    'Местонахождение приурочено к обнажениям пермских отложений на правом берегу реки Вятки. Около 260 миллионов лет назад здесь находилась прибрежная равнина, где обитали парейазавры — крупные растительноядные пресмыкающиеся пермского периода.',
    'Ежегодные весенние паводки и оползневые процессы постоянно обнажают в береговом обрыве новые кости и целые скелеты, что делает это место одним из самых продуктивных палеонтологических объектов в мире.',
  ],
}

const protection = {
  head: 'Режим посещения и охрана',
  text: 'Территория имеет статус памятника природы регионального значения. Самостоятельные раскопки запрещены — сбор палеонтологического материала ведут только специалисты Вятского палеонтологического музея. Посетители могут ознакомиться с местонахождением в рамках экскурсий.',
}
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/nature">Памятники природы</RouterLink><span>/</span>
      <span class="current">Котельничское местонахождение парейазавров</span>
    </nav>

    <section class="intro">
      <div class="frame">
        <span class="frame-emoji" aria-hidden="true">🗻</span>
      </div>
      <div class="tags">
        <span class="tag tag--teal">Геологический комплекс</span>
      </div>
      <h1 class="title">{{ site.name }}</h1>
      <p class="meta">{{ site.location }}</p>
    </section>

    <section class="section">
      <h2 class="heading">{{ origin.head }}</h2>
      <p v-for="(p, i) in origin.paragraphs" :key="i" class="text">{{ p }}</p>

      <h2 class="heading heading--offset">{{ protection.head }}</h2>
      <p class="text">{{ protection.text }}</p>

      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <div class="back-bar">
      <RouterLink to="/nature" class="cta-btn">← К каталогу памятников природы</RouterLink>
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
.frame { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-birch); margin-bottom: var(--space-3); aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center }
.frame-emoji { font-size: 64px; opacity: 0.4 }
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap; margin-bottom: 12px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin-bottom: var(--space-3) }

.section { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3) var(--space-5) }
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
  .stats { grid-template-columns: 1fr }
}
</style>
