<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import heroImg from '@/assets/img/ethnography.jpg'
import kapokorenImg from '@/assets/img/kapokoren.jpg'
import kruzhevoImg from '@/assets/img/kruzhevo.jpg'
import dymkovoImg from '@/assets/img/dymkovo-toys.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import FilterBar from '@/components/FilterBar.vue'

const people = ref('all')
const peopleOptions = [{ value: 'all', label: 'Все' }, { value: 'russian', label: 'Русские' }, { value: 'tatar', label: 'Татары' }, { value: 'udmurt', label: 'Удмурты' }, { value: 'mari', label: 'Марийцы' }, { value: 'komi', label: 'Коми' }]
const type = ref('all')
const typeOptions = [{ value: 'all', label: 'Все' }, { value: 'costume', label: 'Костюм' }, { value: 'utensils', label: 'Утварь' }, { value: 'tools', label: 'Орудия труда' }, { value: 'ritual', label: 'Обряды' }, { value: 'craft', label: 'Промыслы' }]

const peopleLabels = { russian: 'Русские', tatar: 'Татары', udmurt: 'Удмурты', mari: 'Марийцы', komi: 'Коми' }
const typeLabels = { costume: 'Костюм', utensils: 'Утварь', tools: 'Орудия труда', ritual: 'Обряды', craft: 'Промыслы' }

const items = [
  { slug: 'zhenskiy-prazdnichny-kostyum', people: 'russian', type: 'costume', name: 'Женский праздничный костюм', location: 'с. Великорецкое, Юрьянский район', desc: 'Сарафанный комплекс с душегреей и кокошником, расшит золотными нитями и жемчугом.', img: heroImg, x: 260, y: 200 },
  { slug: 'kazansky-kalfak-i-kamzol', people: 'tatar', type: 'costume', name: 'Казанский калфак и камзол', location: 'Малмыжский уезд', desc: 'Бархатный калфак с золотым шитьём и парчовый камзол.', img: heroImg, x: 410, y: 260 },
  { slug: 'udmurtskiy-kompleks-odezhdy', people: 'udmurt', type: 'costume', name: 'Комплекс одежды удмуртов-южан', location: 'Унинский район', desc: 'Белая холщовая рубаха-платье с вышивкой, монисто и налобная повязка.', img: heroImg, x: 320, y: 340 },
  { slug: 'mariyskaya-shurka', people: 'mari', type: 'costume', name: 'Женский головной убор — шурка', location: 'Санчурский район', desc: 'Высокий конусообразный головной убор с нашивками бисера, монет и раковин.', img: heroImg, x: 470, y: 300 },
  { slug: 'tkackiy-stanok-krosna', people: 'russian', type: 'tools', name: 'Ткацкий станок — кросна', location: 'д. Бобино, Слободской район', desc: 'Полный комплект горизонтального ткацкого стана с бердом, челноками и нитченками.', img: kapokorenImg, x: 220, y: 300 },
  { slug: 'ohotnichiy-nabor-komi', people: 'komi', type: 'tools', name: 'Охотничий набор коми-зырян', location: 'Нагорский район', desc: 'Ружьё-кремнёвка, пороховница из рога, нож в деревянных ножнах, лыжи-голицы.', img: kapokorenImg, x: 550, y: 200 },
  { slug: 'derevyannyy-tues', people: 'russian', type: 'utensils', name: 'Деревянный туес', location: 'пгт Фалёнки', desc: 'Цилиндрический сосуд из берёсты с деревянным дном для хранения мёда, ягод, круп.', img: kapokorenImg, x: 260, y: 400 },
  { slug: 'mednyy-samovar', people: 'tatar', type: 'utensils', name: 'Медный самовар и пиала', location: 'Малмыжский уезд', desc: 'Традиционный татарский самовар с чайной пиалой.', img: kapokorenImg, x: 420, y: 400 },
  { slug: 'dolblenyye-kovshi', people: 'udmurt', type: 'utensils', name: 'Долблёные ковши и чаши', location: 'Глазовский уезд', desc: 'Деревянные ковши в форме утицы для подачи пива и кваса.', img: kapokorenImg, x: 360, y: 460 },
  { slug: 'syurem-ritual', people: 'mari', type: 'ritual', name: 'Календарный обряд Сӱрем', location: 'Санчурский район', desc: 'Марийский языческий обряд изгнания злых духов и очищения села.', img: kruzhevoImg, x: 520, y: 380 },
  { slug: 'svyatochnye-gulyaniya', people: 'russian', type: 'ritual', name: 'Святочные гуляния и ряженье', location: 'с. Великорецкое', desc: 'Святочные обходы дворов с колядками, ряженьем в медведя, козу и коня.', img: kruzhevoImg, x: 210, y: 160 },
  { slug: 'dymkovskaya-igrushka', people: 'russian', type: 'craft', name: 'Дымковская игрушка', location: 'слобода Дымково, г. Вятка', desc: 'Глиняная расписная фигурка — один из старейших русских народных промыслов.', img: dymkovoImg, x: 300, y: 240 },
]

const filtered = computed(() => items.filter(it => (people.value === 'all' || it.people === people.value) && (type.value === 'all' || it.type === type.value)).map(it => ({ ...it, peopleLabel: peopleLabels[it.people], typeLabel: typeLabels[it.type] })))
</script>

<template>
  <CatalogLayout :heroImg="heroImg" eyebrow="К 90-летию Кировской области · Народы Вятской земли" title="Этнографическое наследие" subtitle="Уникальная коллекция предметов быта, костюмов, орудий труда и обрядов народов, веками населяющих Вятский край: русских, татар, удмуртов, марийцев и коми" :gridCount="filtered.length">
    <template #heading>Цифровой архив народной культуры</template>
    <template #description>Коллекция собрана экспедициями КГПИ, ВятГГУ и ВятГУ с 1980-х по 2020-е годы. В основе материалов — многолетние полевые исследования. Каждый экспонат сопровождается подробным описанием, указанием места и времени бытования. Представлены полевые записи обрядов, песен, сказаний и народных промыслов.</template>
    <template #filters>
      <FilterBar label="По народу" :options="peopleOptions" :active="people" @select="v => people = v" />
      <FilterBar label="По типу" :options="typeOptions" :active="type" @select="v => type = v" />
    </template>
    <template #grid>
      <RouterLink v-for="it in filtered" :key="it.slug" :to="`/ethnography/${it.slug}`" class="card">
        <div class="card-img" :style="{ backgroundImage: `url(${it.img})` }" role="img" :aria-label="it.name" />
        <div class="card-body">
          <div class="tags"><span class="tag tag--teal">{{ it.peopleLabel }}</span><span class="tag tag--ochre">{{ it.typeLabel }}</span></div>
          <h3 class="card-title">{{ it.name }}</h3>
          <div class="card-meta">{{ it.location }}</div>
          <p class="card-desc">{{ it.desc }}</p>
        </div>
      </RouterLink>
    </template>
  </CatalogLayout>

  <section class="cta">
    <h2 class="cta-heading">Помогите сохранить этнографическое наследие</h2>
    <p class="cta-sub">Если у вас есть предметы народного быта, костюмы, фотографии или записи обрядов — свяжитесь с нами.</p>
    <button type="button" class="cta-btn">Связаться с проектом</button>
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
.card-img { width: 100%; aspect-ratio: 4/3; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 4px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-muted); margin-bottom: 6px }
.card-desc { font-size: 13px; color: var(--color-ink); line-height: 1.5; margin: 0 }
</style>
