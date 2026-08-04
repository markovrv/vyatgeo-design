<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import heroImg from '@/assets/img/nature.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import FilterBar from '@/components/FilterBar.vue'

const type = ref('all')
const typeOptions = [{ value: 'all', label: 'Все типы' }, { value: 'lake', label: 'Озёра' }, { value: 'rock', label: 'Скальные обнажения' }, { value: 'geology', label: 'Геологические комплексы' }, { value: 'waterfall', label: 'Водопады' }, { value: 'cave', label: 'Пещеры' }, { value: 'botany', label: 'Ботанические' }]

const typeLabels = { lake: 'Озёра', rock: 'Скальные обнажения', geology: 'Геологические комплексы', waterfall: 'Водопады', cave: 'Пещеры', botany: 'Ботанические' }

const sites = [
  { slug: 'chvaniha', type: 'lake', name: 'Озеро Чваниха', location: 'Зуевский район · Площадь 15 га', desc: 'Самое глубокое озеро области (до 33 м), карстовое происхождение, окружено сосновым бором.', img: heroImg, x: 200, y: 220 },
  { slug: 'bakuli-shihany', type: 'rock', name: 'Шиханы у с. Бакули', location: 'Слободской район · Протяжённость 1,2 км', desc: 'Обнажения пермских известняков с окаменелостями древних морских организмов.', img: heroImg, x: 300, y: 280 },
  { slug: 'pareyazavry', type: 'geology', name: 'Котельничское местонахождение парейазавров', location: 'Котельничский район · Площадь 1 200 га', desc: 'Уникальное местонахождение скелетов парейазавров пермского периода. Одно из крупнейших в мире.', img: heroImg, x: 420, y: 320 },
  { slug: 'beresnyata-waterfall', type: 'waterfall', name: 'Водопад у д. Береснята', location: 'Верхошижемский район · Высота 3,5 м', desc: 'Один из немногих водопадов области, каскадный, стекает по известняковым плитам.', img: heroImg, x: 360, y: 170 },
  { slug: 'kirovskaya-cave', type: 'cave', name: 'Пещера «Кировская»', location: 'Омутнинский район · Длина 58 м', desc: 'Карстовая пещера в известняках с натёчными образованиями. Обитают колонии летучих мышей.', img: heroImg, x: 480, y: 230 },
  { slug: 'borovikovsky-bor', type: 'botany', name: 'Бор Боровиковский', location: 'Мурашинский район · Площадь 230 га', desc: 'Эталонный участок соснового бора-беломошника, возраст сосен до 250 лет.', img: heroImg, x: 250, y: 150 },
  { slug: 'lezhninskoe', type: 'lake', name: 'Озеро Лежнинское', location: 'Пижанский район · Площадь 12 га', desc: 'Карстовое озеро правильной овальной формы глубиной до 28 м.', img: heroImg, x: 500, y: 390 },
  { slug: 'utyos-dom-otdyha', type: 'rock', name: 'Утёс «Дом отдыха»', location: 'Советский район · Высота 40 м', desc: 'Живописный скальный массив с обнажениями известняков казанского яруса.', img: heroImg, x: 380, y: 420 },
  { slug: 'dendropark', type: 'botany', name: 'Дендрологический парк', location: 'г. Киров · Площадь 21 га', desc: 'Один из старейших дендропарков России (основан в 1912 году), более 400 таксонов деревьев.', img: heroImg, x: 200, y: 350 },
  { slug: 'sokoly-gory', type: 'geology', name: 'Сокольи горы', location: 'Котельничский район · Протяжённость 3 км', desc: 'Скальный массив с обнажениями пермских и триасовых отложений, место гнездования редких птиц.', img: heroImg, x: 560, y: 330 },
  { slug: 'shumyashchiy-kamen', type: 'waterfall', name: 'Шумящий камень', location: 'Нагорский район · Высота 2,5 м', desc: 'Порожистый участок реки Кобры с отвесным уступом из песчаника.', img: heroImg, x: 620, y: 200 },
  { slug: 'nechaevsky-proval', type: 'cave', name: 'Нечаевский провал', location: 'Верхнекамский район · Глубина 16 м', desc: 'Карстовый провал диаметром 20 м с озером на дне.', img: heroImg, x: 550, y: 460 },
]

const filtered = computed(() => sites.filter(s => type.value === 'all' || s.type === type.value).map(s => ({ ...s, typeLabel: typeLabels[s.type] })))
</script>

<template>
  <CatalogLayout :heroImg="heroImg" eyebrow="К 90-летию Кировской области · Уникальная природа Вятского края" title="Памятники природы Кировской области" subtitle="Более 100 уникальных природных объектов: живописные озёра, древние скальные обнажения, водопады, пещеры и ботанические редкости" :gridCount="filtered.length">
    <template #heading>Природное наследие региона</template>
    <template #description>Для каждого памятника указано точное географическое положение, размеры, происхождение и уникальные природные особенности. Представлены сведения об охраняемом статусе, режиме посещения и мерах по сохранению.</template>
    <template #filters>
      <FilterBar :options="typeOptions" :active="type" @select="v => type = v" />
    </template>
    <template #grid>
      <RouterLink v-for="s in filtered" :key="s.slug" :to="`/nature/${s.slug}`" class="card">
        <div class="card-img" :style="{ backgroundImage: `url(${s.img})` }" role="img" :aria-label="s.name" />
        <div class="card-body">
          <div class="tags"><span class="tag tag--teal">{{ s.typeLabel }}</span></div>
          <h3 class="card-title">{{ s.name }}</h3>
          <div class="card-meta">{{ s.location }}</div>
          <p class="card-desc">{{ s.desc }}</p>
        </div>
      </RouterLink>
    </template>
  </CatalogLayout>

  <section class="cta">
    <h2 class="cta-heading">Сохранение природного наследия</h2>
    <p class="cta-sub">Более 100 памятников природы находятся под охраной государства</p>
    <button type="button" class="cta-btn">Карта маршрутов</button>
  </section>
</template>

<style scoped>
.cta { background: var(--color-birch); padding: var(--space-5) var(--space-3); text-align: center }
.cta-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); margin: 0 0 var(--space-2) }
.cta-sub { font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-3) }
.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 14px; font-weight: 500;
  padding: 14px 32px; border: none; cursor: pointer;
  background: var(--color-ochre); color: var(--color-bg); min-height: 44px;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms;
}
.cta-btn:hover { background: var(--color-teal) }
.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 2px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-muted); margin-bottom: 4px }
.card-desc { font-size: 13px; color: var(--color-ink); line-height: 1.5; margin: 0 }
</style>
