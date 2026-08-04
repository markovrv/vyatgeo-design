<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import heroImg from '@/assets/img/victory-hero.jpg'
import victoryImg from '@/assets/img/victory-faces.jpg'
import trifonovImg from '@/assets/img/kremlin.jpeg'
import architectureImg from '@/assets/img/architecture-kirov.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import FilterBar from '@/components/FilterBar.vue'

const era = ref('all')
const eraOptions = [{ value: 'all', label: 'Все периоды' }, { value: 'medieval', label: 'XIV–XVII вв.' }, { value: 'empire', label: 'XVIII–XIX вв.' }, { value: 'wwi', label: 'Первая мировая' }, { value: 'wwii', label: 'Великая Отечественная' }, { value: 'modern', label: 'Новейшее время' }]

const blocks = [
  {
    key: 'medieval', period: 'XIV–XVII века', title: 'От похода на Золотую Орду до Смутного времени',
    heroes: [
      { slug: 'vyatskaya-druzhina', title: 'Вятская дружина', years: 'XV век · поход на Золотую Орду', desc: 'Победный поход вятской дружины на столицу Золотой Орды, заложивший основу воинской традиции края.', img: trifonovImg },
      { slug: 'zashchitniki-khlynova', title: 'Защитники Хлынова', years: 'XVI в. · оборона Вятской земли', desc: 'Вятские ратники, оборонявшие Хлынов от набегов казанских татар и участвовавшие в походах Ивана Грозного.', img: trifonovImg },
    ],
  },
  {
    key: 'empire', period: 'XVIII–XIX века', title: 'Имперские войны: от Северной до русско-турецких',
    heroes: [
      { slug: 'vyatchane-v-severnoy-voyne', title: 'Вятчане в Северной войне', years: '1700–1721 · Северная война', desc: 'Участие вятских полков в Полтавской битве и других сражениях Северной войны под командованием Петра I.', img: architectureImg },
      { slug: 'geroi-1812-goda', title: 'Герои 1812 года', years: '1812–1814 · Отечественная война', desc: 'Уроженцы Вятской губернии, сражавшиеся в Бородинском сражении и заграничных походах русской армии.', img: architectureImg },
      { slug: 'russko-tureckie-kampanii', title: 'Русско-турецкие кампании', years: '1828–1878 · Русско-турецкие войны', desc: 'Вятчане — участники осады Варны, обороны Севастополя, боёв на Шипке и под Плевной.', img: architectureImg },
    ],
  },
  {
    key: 'wwi', period: 'Первая мировая война', title: '1914–1918 · Забытые герои',
    heroes: [
      { slug: 'vyatskie-polki', title: 'Вятские полки', years: '1914–1917 · Первая мировая', desc: 'Солдаты и офицеры из Вятской губернии, участвовавшие в Брусиловском прорыве и оборонительных сражениях.', img: victoryImg },
      { slug: 'georgievskie-kavalery', title: 'Георгиевские кавалеры', years: '1914–1918 · Первая мировая', desc: 'Уроженцы Вятки, награждённые Георгиевским крестом и Георгиевским оружием за храбрость.', img: victoryImg },
    ],
  },
  {
    key: 'wwii', period: 'Великая Отечественная война', title: '1941–1945 · От рядовых до полководцев',
    heroes: [
      { slug: 'boycy-311-y-strelkovoy', title: 'Бойцы 311-й стрелковой', years: '1941–1945 · 311-я стрелковая дивизия', desc: 'Сформированная в Кирове 311-я стрелковая дивизия прошла боевой путь от Ленинграда до Восточной Пруссии.', img: victoryImg },
      { slug: 'geroi-sovetskogo-soyuza', title: 'Герои Советского Союза', years: '1941–1945 · Высшая награда', desc: 'Более 200 уроженцев Кировской области удостоены звания Героя Советского Союза за подвиги на фронтах.', img: victoryImg },
    ],
  },
  {
    key: 'modern', period: 'Новейшее время', title: 'Локальные конфликты и защита рубежей',
    heroes: [
      { slug: 'voiny-internacionalisty', title: 'Воины-интернационалисты', years: '1979–1989 · Афганистан', desc: 'Уроженцы Кировской области, выполнявшие интернациональный долг в Демократической Республике Афганистан.', img: victoryImg },
      { slug: 'zashchitniki-otechestva', title: 'Защитники Отечества', years: '1990–2020-е · Постсоветский период', desc: 'Военнослужащие из Кировской области, участвовавшие в контртеррористических операциях и миротворческих миссиях.', img: victoryImg },
    ],
  },
]

const visibleBlocks = computed(() => blocks.filter(b => era.value === 'all' || b.key === era.value))
const heroCount = computed(() => visibleBlocks.value.reduce((n, b) => n + b.heroes.length, 0))
</script>

<template>
  <CatalogLayout :heroImg="heroImg" eyebrow="К 90-летию Кировской области · Живая летопись ратной славы" title="Лица Победы" subtitle="Воинские подвиги уроженцев Вятской земли с XIV века до наших дней — от похода на Золотую Орду до современных защитников Отечества" :gridCount="heroCount">
    <template #heading>Живая летопись ратной славы</template>
    <template #description>Проект будет постоянно пополняться новыми именами и материалами из архивов. На странице учтены ратные подвиги вятчан в войнах XVIII и XIX столетий, включая Северную войну, Отечественную войну 1812 года и русско-турецкие кампании. Значительный пласт посвящен участникам Первой мировой войны. Широко представлены герои Великой Отечественной войны — от рядовых бойцов до прославленных полководцев. Отдельный раздел — участники локальных конфликтов и войн новейшего времени.</template>
    <template #filters>
      <FilterBar label="По периоду" :options="eraOptions" :active="era" @select="v => era = v" />
    </template>
    <template #grid>
      <div class="timeline">
        <div class="timeline-rail" />
        <section v-for="block in visibleBlocks" :key="block.key" class="timeline-block">
          <div class="timeline-head">
            <span class="timeline-dot" aria-hidden="true" />
            <h3 class="timeline-period">{{ block.period }}</h3>
            <p class="timeline-title">{{ block.title }}</p>
          </div>
          <div class="timeline-cards">
            <RouterLink v-for="h in block.heroes" :key="h.slug" :to="`/victory/${h.slug}`" class="card">
              <div class="card-img" :style="{ backgroundImage: `url(${h.img})` }" role="img" :aria-label="h.title" />
              <div class="card-body">
                <h4 class="card-title">{{ h.title }}</h4>
                <div class="card-years">{{ h.years }}</div>
                <p class="card-desc">{{ h.desc }}</p>
                <span class="card-more">Подробнее →</span>
              </div>
            </RouterLink>
          </div>
        </section>
      </div>
    </template>
  </CatalogLayout>

  <section class="cta">
    <h2 class="cta-heading">Помогите сохранить память</h2>
    <p class="cta-sub">Если у вас есть фотографии, письма или документы ваших родных — участников войн, пришлите их нам для оцифровки и публикации.</p>
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

.timeline { position: relative; max-width: 900px; margin: 0 auto; grid-column: 1 / -1 }
.timeline-rail { position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: var(--color-ochre) }
.timeline-block { position: relative; padding-left: var(--space-5); margin-bottom: var(--space-5) }
.timeline-block:last-child { margin-bottom: 0 }
.timeline-head { position: relative; margin-bottom: var(--space-3) }
.timeline-dot { position: absolute; left: calc(-1 * var(--space-5)); top: 6px; width: 16px; height: 16px; background: var(--color-ochre); border-radius: 50%; border: 3px solid var(--color-bg); box-shadow: 0 0 0 2px var(--color-ochre) }
.timeline-period { font-family: var(--font-display); font-size: 22px; font-weight: 700; color: var(--color-teal); margin: 0 0 4px }
.timeline-title { font-size: 15px; color: var(--color-muted); margin: 0 }
.timeline-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--space-3) }
.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 1/1; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.card-title { font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--color-ink); margin: 0 0 4px; line-height: 1.3 }
.card-years { font-size: 12px; color: var(--color-teal); letter-spacing: 0.04em; margin-bottom: 6px }
.card-desc { font-size: 13px; color: var(--color-muted); line-height: 1.6; margin: 0 0 var(--space-1) }
.card-more { font-size: 13px; color: var(--color-ochre); text-decoration: underline; text-underline-offset: 2px }
</style>
