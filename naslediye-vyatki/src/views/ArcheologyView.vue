<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import heroImg from '@/assets/img/archeology.jpg'
import natureImg from '@/assets/img/nature.jpg'
import kremlinImg from '@/assets/img/kremlin.jpeg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import CatalogMap from '@/components/CatalogMap.vue'
import FilterBar from '@/components/FilterBar.vue'

// All catalog cards and map points lead to the same representative detail
// page, matching the original mockup (ArcheologySite.dc.html has no
// per-item data — every card links to the one example page).
const DETAIL_SLUG = 'khlynovsky-kremlin'

const era = ref('all')
const eraOptions = [
  { value: 'all', label: 'Все эпохи' },
  { value: 'mezolit', label: 'Мезолит' },
  { value: 'neolit', label: 'Неолит' },
  { value: 'bronze', label: 'Эпоха бронзы' },
  { value: 'iron', label: 'Ранний железный век' },
  { value: 'medieval', label: 'Средневековье' },
]
const eraLabels = Object.fromEntries(eraOptions.map(o => [o.value, o.label]))

const type = ref('all')
const typeOptions = [
  { value: 'all', label: 'Все типы' },
  { value: 'gorodishche', label: 'Городище' },
  { value: 'selishche', label: 'Селище' },
  { value: 'stoyanka', label: 'Стоянка' },
  { value: 'mogilnik', label: 'Могильник' },
  { value: 'klad', label: 'Клад' },
]
const typeLabels = Object.fromEntries(typeOptions.map(o => [o.value, o.label]))

const viewMode = ref('map')
const viewOptions = [{ value: 'map', label: 'Карта' }, { value: 'catalog', label: 'Каталог' }]

const stats = [
  { n: '~150', label: 'памятников на карте' },
  { n: '8 000+', label: 'лет истории региона' },
  { n: '5', label: 'основных эпох' },
]

// Map points — a separate dataset from the catalog cards below (this
// mirrors the source mockup, where the SVG map markers and the catalog
// list are independently defined and only partially overlap).
const pointDefs = [
  { name: 'Шиханы-1', x: 180, y: 200, era: 'mezolit', type: 'stoyanka' },
  { name: 'Белая Локша', x: 320, y: 450, era: 'neolit', type: 'stoyanka' },
  { name: 'Ананьинское городище', x: 280, y: 280, era: 'bronze', type: 'gorodishche' },
  { name: 'Буйское городище', x: 500, y: 300, era: 'bronze', type: 'gorodishche' },
  { name: 'Пижемское городище', x: 400, y: 500, era: 'iron', type: 'gorodishche' },
  { name: 'Шиховское городище', x: 200, y: 350, era: 'iron', type: 'gorodishche' },
  { name: 'Кировское городище', x: 440, y: 220, era: 'iron', type: 'gorodishche' },
  { name: 'Хлыновский кремль', x: 380, y: 250, era: 'medieval', type: 'gorodishche' },
  { name: 'Котельничское городище', x: 250, y: 180, era: 'medieval', type: 'gorodishche' },
  { name: 'Уржумское селище', x: 580, y: 400, era: 'medieval', type: 'selishche' },
]
const points = computed(() => pointDefs.map(p => {
  const visible = (era.value === 'all' || era.value === p.era) && (type.value === 'all' || type.value === p.type)
  return { ...p, tx: p.x + 5, ty: p.y - 5, opacity: visible ? 1 : 0.2, visible }
}))

const siteData = [
  { era: 'mezolit', type: 'stoyanka', name: 'Шиханы-1', location: 'Котельничский район, правый берег р. Вятки', desc: 'Многослойная стоянка эпохи мезолита. Найдены кремнёвые орудия: скребки, резцы, ножи.', img: natureImg },
  { era: 'neolit', type: 'stoyanka', name: 'Белая Локша', location: 'Слободской район, озеро Белая Локша', desc: 'Неолитическая стоянка на берегу озера. Характерна керамика с гребенчато-накольчатым орнаментом.', img: heroImg },
  { era: 'bronze', type: 'gorodishche', name: 'Ананьинское городище', location: 'Малмыжский район, р. Вятка', desc: 'Эталонный памятник ананьинской культуры. Найдены бронзовые кельты, наконечники копий, керамика.', img: heroImg },
  { era: 'bronze', type: 'gorodishche', name: 'Буйское городище', location: 'Кирово-Чепецкий район, р. Чепца', desc: 'Укреплённое поселение ананьинской культуры на высоком мысу.', img: heroImg },
  { era: 'iron', type: 'gorodishche', name: 'Шиховское городище', location: 'Оричевский район, р. Вятка', desc: 'Городище ананьинской культуры на высоком мысу с остатками вала и рва.', img: natureImg },
  { era: 'iron', type: 'gorodishche', name: 'Пижемское городище', location: 'Советский район, р. Пижма', desc: 'Укреплённое поселение ананьинской культуры на останце коренного берега.', img: heroImg },
  { era: 'medieval', type: 'gorodishche', name: 'Хлыновский кремль', location: 'г. Киров, исторический центр', desc: 'Детинец средневекового Хлынова (XII–XV вв.) с остатками деревянных укреплений.', img: kremlinImg },
  { era: 'medieval', type: 'gorodishche', name: 'Котельничское городище', location: 'г. Котельнич, центральная часть', desc: 'Остатки средневекового города, известного с XII в., с земляным валом до 4 м.', img: kremlinImg },
  { era: 'medieval', type: 'mogilnik', name: 'Вятский некрополь', location: 'г. Киров, район бывшего кремля', desc: 'Средневековый некрополь Хлынова с более чем 50 погребениями.', img: heroImg },
  { era: 'medieval', type: 'klad', name: 'Клад серебряных монет', location: 'Киров, ул. Московская', desc: 'Клад из 136 серебряных монет XVI–XVII вв., обнаруженный в 1968 году.', img: heroImg },
]
const sites = siteData.map(s => ({ ...s, eraLabel: eraLabels[s.era], typeLabel: typeLabels[s.type] }))
const filtered = computed(() => sites.filter(s => (era.value === 'all' || s.era === era.value) && (type.value === 'all' || s.type === type.value)))
</script>

<template>
  <CatalogLayout :heroImg="heroImg" eyebrow="К 90-летию Кировской области · От мезолита до Средневековья" title="Памятники археологии Кировской области" subtitle="Около 150 памятников от эпохи мезолита до позднего Средневековья: городища, селища, стоянки, могильники" :gridCount="viewMode === 'catalog' ? filtered.length : 0">
    <template #heading>Памятники на карте и в каталоге</template>
    <template #description>
      <div class="stats">
        <div v-for="s in stats" :key="s.label" class="stat">
          <strong class="stat-n">{{ s.n }}</strong>
          <span class="stat-label">{{ s.label }}</span>
        </div>
      </div>
      <p class="filters-note">Фильтрация по эпохе и типу памятника — доступна в обоих режимах просмотра</p>
    </template>
    <template #filters>
      <FilterBar label="По эпохе" :options="eraOptions" :active="era" @select="v => era = v" />
      <FilterBar label="По типу" :options="typeOptions" :active="type" @select="v => type = v" />
      <FilterBar label="Режим отображения" :options="viewOptions" :active="viewMode" @select="v => viewMode = v" />
    </template>
    <template v-if="viewMode === 'map'" #map>
      <CatalogMap viewBox="0 0 800 700">
        <path d="M 120 80 L 680 60 L 760 200 L 720 380 L 680 500 L 580 620 L 400 660 L 220 640 L 100 500 L 60 320 L 80 160 Z" fill="var(--color-oak)" stroke="var(--color-ochre)" stroke-width="1.5" stroke-dasharray="6 3" />
        <path d="M 160 140 Q 240 180 300 240 Q 360 300 400 320 Q 480 380 520 400 Q 600 440 660 420" fill="none" stroke="var(--color-teal)" stroke-width="3" opacity="0.5" />
        <RouterLink v-for="p in points" :key="p.name" :to="{ name: 'archeology-site', params: { slug: DETAIL_SLUG } }" :aria-label="p.name" :style="{ opacity: p.opacity, pointerEvents: p.visible ? 'auto' : 'none' }">
          <circle :cx="p.x" :cy="p.y" r="5" fill="var(--color-ochre)" stroke="var(--color-bg)" stroke-width="2" class="map-dot" />
          <text :x="p.tx" :y="p.ty" font-family="var(--font-body)" font-size="10" fill="var(--color-ink)">{{ p.name }}</text>
        </RouterLink>
      </CatalogMap>
      <div class="legend">
        <span class="legend-item"><span class="legend-dot" />Памятник археологии</span>
        <span class="legend-item"><span class="legend-line" />Река Вятка</span>
      </div>
    </template>
    <template #grid>
      <template v-if="viewMode === 'catalog'">
        <RouterLink v-for="s in filtered" :key="s.name" :to="{ name: 'archeology-site', params: { slug: DETAIL_SLUG } }" class="card">
          <div class="card-img" :style="{ backgroundImage: `url(${s.img})` }" role="img" :aria-label="s.name" />
          <div class="card-body">
            <div class="tags"><span class="tag tag--teal">{{ s.eraLabel }}</span><span class="tag tag--ochre">{{ s.typeLabel }}</span></div>
            <h3 class="card-title">{{ s.name }}</h3>
            <div class="card-meta">{{ s.location }}</div>
            <p class="card-desc">{{ s.desc }}</p>
          </div>
        </RouterLink>
      </template>
    </template>
  </CatalogLayout>
</template>

<style scoped>
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-2); margin-bottom: var(--space-3) }
.stat { text-align: center; padding: var(--space-2); border: 1.5px dashed var(--color-border); border-radius: var(--radius); background: var(--color-surface) }
.stat-n { display: block; font-family: var(--font-display); font-size: 32px; color: var(--color-teal) }
.stat-label { font-size: 13px; color: var(--color-muted) }
.filters-note { text-align: center; font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-3) }

.map-dot { cursor: pointer; transition: fill 200ms }
.map-dot:hover { fill: var(--color-teal) }
.legend { display: flex; flex-wrap: wrap; gap: var(--space-2); justify-content: center; margin-top: var(--space-2); font-size: 12px; color: var(--color-muted) }
.legend-item { display: inline-flex; align-items: center; gap: 6px }
.legend-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: var(--color-ochre); border: 2px solid var(--color-bg); box-shadow: 0 0 0 1px var(--color-ochre) }
.legend-line { display: inline-block; width: 20px; height: 3px; background: var(--color-teal); opacity: 0.6; border-radius: 2px }

.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit; transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 2px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-teal); margin-bottom: 4px }
.card-desc { font-size: 13px; color: var(--color-ink); line-height: 1.5; margin: 0 }

@media (max-width: 767px) {
  .stats { grid-template-columns: 1fr }
}
</style>
