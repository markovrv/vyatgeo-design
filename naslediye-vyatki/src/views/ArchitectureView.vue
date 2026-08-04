<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import heroImg from '@/assets/img/architecture-kirov.jpg'
import kremlinImg from '@/assets/img/kremlin.jpeg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import CatalogMap from '@/components/CatalogMap.vue'
import FilterBar from '@/components/FilterBar.vue'

const style = ref('all')
const styleOptions = [{ value: 'all', label: 'Все стили' }, { value: 'classicism', label: 'Классицизм' }, { value: 'modern', label: 'Модерн' }, { value: 'constructivism', label: 'Конструктивизм' }, { value: 'stalin', label: 'Сталинский ампир' }, { value: 'eclectic', label: 'Эклектика' }]
const status = ref('all')
const statusOptions = [{ value: 'all', label: 'Все' }, { value: 'preserved', label: 'Сохранился' }, { value: 'lost', label: 'Утрачен' }]
const viewMode = ref('map')
const viewOptions = [{ value: 'map', label: 'На карте' }, { value: 'catalog', label: 'Каталог' }]

const styleLabels = { classicism: 'Классицизм', modern: 'Модерн', constructivism: 'Конструктивизм', stalin: 'Сталинский ампир', eclectic: 'Эклектика' }
const statusLabels = { preserved: 'Сохранился', lost: 'Утрачен' }

const buildings = [
  { slug: 'trifonov-monastery', style: 'classicism', status: 'preserved', name: 'Успенский Трифонов монастырь', meta: '1580 г. · ул. Горбачева, 1', desc: 'Первая каменная обитель Вятки, основанная монахом Трифоном.', img: kremlinImg },
  { slug: 'gorodskaya-duma', style: 'classicism', status: 'preserved', name: 'Здание бывшей городской думы', meta: 'нач. XIX в. · ул. Ленина, 82', desc: 'Каменное двухэтажное здание с портиком и фронтоном.', img: heroImg },
  { slug: 'bulychev-house', style: 'modern', status: 'preserved', name: 'Дом купца Булычёва', meta: '1910 г. · ул. Ленина, 72', desc: 'Особняк в стиле модерн с элементами эклектики, лепниной и растительным орнаментом.', img: heroImg },
  { slug: 'prozorov-house', style: 'modern', status: 'preserved', name: 'Доходный дом Прозорова', meta: '1913 г. · ул. Московская, 37', desc: 'Четырёхэтажный доходный дом с эркерами и балконами в стиле северного модерна.', img: heroImg },
  { slug: 'serafimovsky-sobor', style: 'classicism', status: 'lost', name: 'Свято-Серафимовский собор', meta: '1900–1904 гг. · утрачен в 1930-х', desc: 'Крупнейший храм Вятки, разрушен в 1932 году.', img: kremlinImg },
  { slug: 'dom-kommuna', style: 'constructivism', status: 'preserved', name: 'Дом-коммуна «Гостиница»', meta: '1929 г. · ул. Дрелевского, 10', desc: 'Один из первых домов конструктивизма в Вятке.', img: heroImg },
  { slug: 'zh-d-vokzal', style: 'stalin', status: 'preserved', name: 'Здание железнодорожного вокзала', meta: '1952 г. · пл. Привокзальная, 1', desc: 'Образец сталинского ампира с портиком, колоннами и шпилем.', img: heroImg },
  { slug: 'klabukov-osobnyak', style: 'eclectic', status: 'preserved', name: 'Купеческий особняк Клабукова', meta: 'кон. XIX в. · ул. Спасская, 38', desc: 'Двухэтажный особняк, сочетающий классицизм, барокко и неоренессанс.', img: heroImg },
  { slug: 'spassky-sobor', style: 'classicism', status: 'lost', name: 'Спасский собор', meta: 'кон. XVII в. · утрачен в 1937', desc: 'Один из первых каменных храмов Вятки в стиле нарышкинского барокко.', img: kremlinImg },
  { slug: 'kirovsky-cirk', style: 'constructivism', status: 'preserved', name: 'Кировский цирк', meta: '1933 г. · ул. К. Маркса, 18', desc: 'Один из старейших цирков России, цилиндрический объём с ленточным остеклением.', img: heroImg },
]

const filtered = computed(() => buildings.filter(b => (style.value === 'all' || b.style === style.value) && (status.value === 'all' || b.status === status.value)).map(b => ({ ...b, styleLabel: styleLabels[b.style], statusLabel: statusLabels[b.status] })))

const pointDefs = [
  { slug: 'trifonov-monastery', name: 'Трифонов монастырь', x: 120, y: 380, style: 'classicism', status: 'preserved' },
  { slug: 'gorodskaya-duma', name: 'Городская дума', x: 220, y: 300, style: 'classicism', status: 'preserved' },
  { slug: 'bulychev-house', name: 'Дом Булычёва', x: 300, y: 340, style: 'modern', status: 'preserved' },
  { slug: 'prozorov-house', name: 'Дом Прозорова', x: 380, y: 260, style: 'modern', status: 'preserved' },
  { slug: 'zh-d-vokzal', name: 'Ж/д вокзал', x: 480, y: 400, style: 'stalin', status: 'preserved' },
  { slug: 'dom-kommuna', name: 'Дом-коммуна', x: 560, y: 220, style: 'constructivism', status: 'preserved' },
  { slug: 'kirovsky-cirk', name: 'Кировский цирк', x: 640, y: 320, style: 'constructivism', status: 'preserved' },
  { slug: 'zashihina-usadba', name: 'Усадьба Зашихина', x: 700, y: 180, style: 'eclectic', status: 'preserved' },
]
const mapPoints = computed(() => pointDefs.map(p => {
  const visible = (style.value === 'all' || p.style === style.value) && (status.value === 'all' || p.status === status.value)
  return { ...p, tx: p.x + 10, ty: p.y - 6, opacity: visible ? 1 : 0.2, visible }
}))
</script>

<template>
  <CatalogLayout :heroImg="heroImg" eyebrow="К 90-летию Кировской области · От деревянного Хлынова до каменной Вятки" title="Архитектура Кирова" subtitle="Цифровой архив архитектурного облика областного центра — сохранившиеся и утраченные памятники зодчества и скульптуры" :gridCount="filtered.length">
    <template #heading>Архитектурное наследие города</template>
    <template #description>Архитектурный облик Кирова (Вятки) складывался веками: от деревянных укреплений Хлынова до каменных особняков в стиле провинциального классицизма, купеческих домов в стиле модерн и советского конструктивизма.</template>
    <template #filters>
      <FilterBar label="По стилю" :options="styleOptions" :active="style" @select="v => style = v" />
      <FilterBar label="Сохранность" :options="statusOptions" :active="status" @select="v => status = v" />
      <FilterBar label="Режим" :options="viewOptions" :active="viewMode" @select="v => viewMode = v" />
    </template>
    <template v-if="viewMode === 'map'" #map>
      <CatalogMap viewBox="0 0 800 500">
        <rect x="20" y="20" width="760" height="460" rx="8" fill="var(--color-oak)" stroke="var(--color-ochre)" stroke-width="1.5" stroke-dasharray="6 3" />
        <path d="M 60 460 Q 200 380 340 400 Q 480 420 740 340" fill="none" stroke="var(--color-teal)" stroke-width="3" opacity="0.5" />
        <RouterLink v-for="p in mapPoints" :key="p.slug" :to="`/architecture/${p.slug}`" :aria-label="p.name" :style="{ opacity: p.opacity, pointerEvents: p.visible ? 'auto' : 'none' }">
          <circle :cx="p.x" :cy="p.y" r="6" fill="var(--color-ochre)" stroke="var(--color-bg)" stroke-width="2" class="map-dot" />
          <text :x="p.tx" :y="p.ty" font-family="var(--font-body)" font-size="11" fill="var(--color-ink)">{{ p.name }}</text>
        </RouterLink>
      </CatalogMap>
    </template>
    <template #grid>
      <template v-if="viewMode === 'catalog'" v-for="b in filtered" :key="b.slug">
        <RouterLink :to="`/architecture/${b.slug}`" class="card">
          <div class="card-img" :style="{ backgroundImage: `url(${b.img})` }" role="img" :aria-label="b.name" />
          <div class="card-body">
            <div class="tags"><span class="tag tag--teal">{{ b.styleLabel }}</span><span class="tag" :class="b.status === 'lost' ? 'tag--muted' : 'tag--ochre'">{{ b.statusLabel }}</span></div>
            <h3 class="card-title">{{ b.name }}</h3>
            <div class="card-meta">{{ b.meta }}</div>
            <p class="card-desc">{{ b.desc }}</p>
          </div>
        </RouterLink>
      </template>
    </template>
  </CatalogLayout>
</template>

<style scoped>
.map-dot { cursor: pointer; transition: fill 200ms }
.map-dot:hover { fill: var(--color-teal) }
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
.tag--ochre { background: var(--color-ochre) }
.tag--muted { background: var(--color-muted) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 2px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-muted); margin-bottom: 4px }
.card-desc { font-size: 13px; color: var(--color-ink); line-height: 1.5; margin: 0 }
</style>
