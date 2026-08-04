<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import image from '@/assets/img/kremlin.jpeg'

const building = ref({
  name: 'Успенский Трифонов монастырь',
  address: 'ул. Горбачева, 1, г. Киров · основан в 1580 году',
})

const photos = [
  { src: image, caption: 'Вид на монастырь с реки Вятки' },
]

const history = {
  historic: 'Монастырь основан монахом Трифоном Вятским на высоком берегу реки Вятки — там же, где ранее находился укреплённый посад Хлынова. Место было выбрано как духовный центр, обозревающий город и подступы к нему.',
  modern: 'Сегодня монастырский комплекс расположен в историческом центре Кирова, на улице Горбачева. Территория открыта для посещения, действует как мужской монастырь Вятской епархии.',
}

const styleText = [
  'Главный храм комплекса — Успенский собор — построен в стиле провинциального классицизма с элементами русского узорочья: пятиглавое завершение, декоративные наличники окон, аркатурный пояс на апсидах.',
  'Монастырский комплекс включает братские кельи, надвратную колокольню и хозяйственные постройки XVII–XIX веков, образующие цельный архитектурный ансамбль.',
]
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/architecture">Архитектура Кирова</RouterLink><span>/</span>
      <span class="current">Успенский Трифонов монастырь</span>
    </nav>

    <section class="intro">
      <div class="frame">
        <img :src="image" alt="Успенский Трифонов монастырь" />
      </div>
      <div class="tags">
        <span class="tag tag--teal">Классицизм</span>
        <span class="tag tag--ochre">Сохранился</span>
      </div>
      <h1 class="title">{{ building.name }}</h1>
      <p class="meta">{{ building.address }}</p>
    </section>

    <section class="section">
      <div class="cols">
        <div>
          <h2 class="heading">Историческое местоположение</h2>
          <p class="text">{{ history.historic }}</p>
        </div>
        <div>
          <h2 class="heading">Современное местоположение</h2>
          <p class="text">{{ history.modern }}</p>
        </div>
      </div>

      <h2 class="heading heading--offset">Стиль и декор</h2>
      <p v-for="(p, i) in styleText" :key="i" class="text">{{ p }}</p>

      <h2 class="heading heading--offset">Архивные изображения</h2>
      <div class="photo-grid">
        <div v-for="p in photos" :key="p.caption" class="photo">
          <img :src="p.src" :alt="p.caption" />
          <div class="photo-caption">{{ p.caption }}</div>
        </div>
        <div class="photo photo--empty" aria-hidden="true">
          <span class="photo-icon">🕍</span>
        </div>
      </div>
    </section>

    <div class="back-bar">
      <RouterLink to="/architecture" class="cta-btn">← К карте и каталогу</RouterLink>
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

.photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: var(--space-2) }
.photo { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-surface) }
.photo img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; filter: sepia(20%) }
.photo-caption { padding: var(--space-1); font-size: 12px; color: var(--color-muted); text-align: center }
.photo--empty { background: var(--color-birch); display: flex; align-items: center; justify-content: center; aspect-ratio: 4/3 }
.photo-icon { font-size: 40px; opacity: 0.4 }

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
}
</style>
