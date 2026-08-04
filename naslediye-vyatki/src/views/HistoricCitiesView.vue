<script setup>
import { ref, computed } from 'vue'
import heroCities from '@/assets/img/historic-cities.jpg'
import kremlinImg from '@/assets/img/kremlin.jpeg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import CatalogMap from '@/components/CatalogMap.vue'
import FilterBar from '@/components/FilterBar.vue'

const era = ref('all')
const viewMode = ref('map')

const cities = [
  { slug: 'kirov', name: 'Киров', x: 380, y: 300, era: 'early', meta: 'Основан: 1374 · Областной центр', desc: 'Главный город области: от древнего поселения новгородцев до крупного промышленного и культурного центра.', img: kremlinImg },
  { slug: 'kotelnich', name: 'Котельнич', x: 240, y: 200, era: 'early', meta: 'Первое упоминание: 1143', desc: 'Древний город на реке Вятке, известный уникальным палеонтологическим местонахождением.', img: heroCities },
  { slug: 'orlov', name: 'Орлов', x: 300, y: 260, era: 'mid', meta: 'Основан: 1459', desc: 'Небольшой купеческий город с застройкой провинциального классицизма.', img: heroCities },
  { slug: 'slobodskoy', name: 'Слободской', x: 430, y: 220, era: 'mid', meta: 'Основан: 1505', desc: 'Крупнейший торговый город Вятской земли с выдающимися памятниками архитектуры.', img: heroCities },
  { slug: 'yaransk', name: 'Яранск', x: 220, y: 400, era: 'late', meta: 'Первое упоминание: 1584', desc: 'Культурный центр юго-запада области с архитектурными памятниками классицизма.', img: heroCities },
  { slug: 'malmyzh', name: 'Малмыж', x: 500, y: 460, era: 'mid', meta: 'Основан: XVI век', desc: 'Торговый центр на пути из Вятки в Казань и Пермь.', img: heroCities },
  { slug: 'urzhum', name: 'Уржум', x: 400, y: 500, era: 'late', meta: 'Основан: 1584 · Родина С.М. Кирова', desc: 'Город, известный как место рождения советского государственного деятеля.', img: heroCities },
]

const eraOptions = [{ value: 'all', label: 'Все века' }, { value: 'early', label: 'До XV века' }, { value: 'mid', label: 'XV–XVI века' }, { value: 'late', label: 'Конец XVI века' }]
const viewOptions = [{ value: 'map', label: 'На карте' }, { value: 'catalog', label: 'Каталог' }]

const filtered = computed(() => era.value === 'all' ? cities : cities.filter(c => c.era === era.value))
</script>

<template>
  <CatalogLayout :heroImg="heroCities" eyebrow="Исторические города · К 90-летию Кировской области" title="Исторические города Вятской земли" subtitle="Семь городов, каждый из которых хранит свою уникальную историю — от крепостей и купеческих центров до современных городов" :gridCount="filtered.length" :showGrid="false">
    <template #heading>Города на карте и в каталоге</template>
    <template #description>Семь исторических городов: Киров (Вятка / Хлынов), Котельнич, Орлов, Слободской, Яранск, Малмыж и Уржум. Нажмите на метку или карточку, чтобы открыть ленту времени города.</template>
    <template #filters>
      <FilterBar label="По веку основания" :options="eraOptions" :active="era" @select="v => era = v" />
      <FilterBar label="Режим отображения" :options="viewOptions" :active="viewMode" @select="v => viewMode = v" />
    </template>
    <template v-if="viewMode === 'map'" #map>
      <CatalogMap viewBox="0 0 800 620">
        <path d="M 100 70 L 660 60 L 740 190 L 700 360 L 640 490 L 540 570 L 360 590 L 180 560 L 90 430 L 60 260 L 70 140 Z" fill="#E8DFC8" stroke="#C27E3A" stroke-width="1.5" stroke-dasharray="6 3" />
        <path d="M 140 130 Q 220 180 300 250 Q 380 320 440 360 Q 540 420 620 400" fill="none" stroke="#3C7A8C" stroke-width="3" opacity="0.5" />
        <template v-for="c in filtered" :key="c.slug">
          <a :href="`/cities/${c.slug}`" class="map-marker">
            <circle :cx="c.x" :cy="c.y" r="9" fill="#C27E3A" stroke="#FAF9F4" stroke-width="2" class="map-dot" />
            <text :x="c.x > 550 ? c.x - 60 : c.x + 14" :y="c.y + 4" class="map-label">{{ c.name }}</text>
          </a>
        </template>
      </CatalogMap>
    </template>
    <template #grid>
      <div v-if="viewMode === 'catalog'" class="grid-count">Показано: {{ filtered.length }} городов</div>
      <template v-if="viewMode === 'catalog'" v-for="c in filtered" :key="c.slug">
        <RouterLink :to="`/cities/${c.slug}`" class="city-card">
          <div class="city-img" :style="{ backgroundImage: `url(${c.img})` }" />
          <div class="city-body">
            <h3 class="city-name">{{ c.name }}</h3>
            <p class="city-meta">{{ c.meta }}</p>
            <p class="city-desc">{{ c.desc }}</p>
            <span class="btn-sm">Лента времени →</span>
          </div>
        </RouterLink>
      </template>
    </template>
  </CatalogLayout>
</template>

<style scoped>
.map-dot { transition: fill 200ms; cursor: pointer }
.map-dot:hover { fill: var(--color-teal) }
.map-label { font-family: var(--font-body); font-size: 13px; fill: var(--color-ink); pointer-events: none }
.grid-count { grid-column: 1 / -1; text-align: center; font-size: 13px; color: var(--color-muted); margin-bottom: var(--space-3) }
.btn-sm {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 500;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-bg);
  min-height: 44px;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
}
.city-card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.city-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.city-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.city-body { padding: var(--space-3) }
.city-name { font-family: var(--font-display); font-weight: 700; font-size: 20px; color: var(--color-ink); margin: 0 0 4px }
.city-meta { font-size: 13px; color: var(--color-teal); letter-spacing: 0.04em; margin: 0 0 8px }
.city-desc { font-size: 14px; color: var(--color-muted); line-height: 1.6; margin: 0 }
</style>
