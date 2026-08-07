<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, onBeforeRouteLeave, useRoute } from 'vue-router'
import fallbackHero from '@/assets/img/kremlin.jpeg'
import FilterBar from '@/components/FilterBar.vue'
import { useHistoryCity, useHistoryEvents, useHistoryThumbnails, historyTimelineState, SITE_URL } from '@/composables/useHistory'

const route = useRoute()
const { city, loading: cityLoading, error: cityError, fetchCity } = useHistoryCity()
const { events, total, hasMore, loading: eventsLoading, loadingMore, error: eventsError, fetchEvents, fetchMore } = useHistoryEvents()
const { thumbnails, fetchThumbnails } = useHistoryThumbnails()

// Прямая ссылка в админку WP на термин таксономии «Город» — для редакторов
// контента, не для посетителей, поэтому неприметная ссылка, а не кнопка
// (тот же паттерн, что editUrl на странице события/объекта).
const editCityUrl = computed(() => city.value ? `${SITE_URL}wp-admin/term.php?taxonomy=city&tag_ID=${city.value.id}&post_type=history` : '')

// Только загрузка города решает, показывать ли страницу целиком (герой,
// сведения, лента) — иначе перезагрузка событий при смене фильтра/поиска
// схлопывала бы всю страницу до одной строки "Загрузка…" и сбрасывала
// прокрутку к началу. Загрузка самих событий обрабатывается локально внутри
// .timeline-section, не трогая герой/сведения/панель управления.
const loading = computed(() => cityLoading.value)
const error = computed(() => cityError.value || eventsError.value)

const centuryOptions = [
  { value: 'all', label: 'Все века' },
  { value: '14-17', label: '14–17 века' },
  { value: '18', label: '18 век' },
  { value: '19', label: '19 век' },
  { value: '20', label: '20 век' },
  { value: '21', label: '21 век' },
]

function eventImg(e) {
  return thumbnails.value[e.imgId] || e.imgSrc
}
watch(events, items => fetchThumbnails(items.map(e => e.imgId)), { immediate: true })

// Фильтры (век, поиск) и уже подгруженные страницы событий и так переживают
// переход на страницу события и обратно — они лежат в module-level singleton
// (historyTimelineState/кэш внутри useHistory.js), а не в локальном состоянии
// компонента. А вот при переходе на ДРУГОЙ город (не "назад", а именно смена
// города) прежняя прокрутка неприменима — сбрасываем её здесь, а не в
// historyTimelineState по умолчанию, иначе она была бы 0 при каждом первом
// открытии и её нельзя было бы отличить от "вернулись с прокруткой 0".
async function load(slug) {
  if (historyTimelineState.citySlug && historyTimelineState.citySlug !== slug) {
    historyTimelineState.scrollY = 0
  }
  historyTimelineState.citySlug = slug
  await Promise.all([
    fetchCity(slug),
    fetchEvents(slug, historyTimelineState.century, historyTimelineState.search),
  ])
}

// Фильтр по веку и поиск — оба серверные (см. history/v1/events): смена
// любого из них всегда перезапрашивает первую страницу заново, а не
// фильтрует уже подгруженный кусок ленты на клиенте (иначе первая страница
// могла бы прийти пустой, хотя подходящие события есть дальше).
async function selectCentury(value) {
  historyTimelineState.century = value
  await fetchEvents(route.params.citySlug, value, historyTimelineState.search)
}

// Тот же фильтр продублирован под лентой — там пользователь уже прокручен
// вниз, поэтому после смены века переносим его к началу списка событий,
// чтобы сразу видеть новый результат. Верхний фильтр, наоборот, не должен
// двигать страницу вообще — им пользуются, никуда не прокрутившись.
const timelineTop = ref(null)
async function selectCenturyFromBottom(value) {
  await selectCentury(value)
  await nextTick()
  timelineTop.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

let searchDebounce = null
function onSearchInput(e) {
  historyTimelineState.search = e.target.value
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    fetchEvents(route.params.citySlug, historyTimelineState.century, historyTimelineState.search)
  }, 400)
}

onMounted(async () => {
  await load(route.params.citySlug)
  // nextTick гарантирует только то, что DOM пропатчен, но браузер может ещё
  // не успеть закончить layout/paint — scrollTo в этот момент получит
  // "зажатую" по текущей, ещё не окончательной высоте документа позицию.
  // Ждём кадр отрисовки (см. тот же паттерн в EthnographyView.vue).
  await nextTick()
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))
  if (historyTimelineState.scrollY) window.scrollTo(0, historyTimelineState.scrollY)
})
watch(() => route.params.citySlug, slug => load(slug))

// Запоминаем прокрутку прямо перед уходом со страницы (клик по событию) —
// на неё же вернёмся при возврате в ленту, вместе с теми же фильтрами
// (век/поиск), которые и так не сбрасываются при уходе.
onBeforeRouteLeave(() => {
  historyTimelineState.scrollY = window.scrollY
})

// Подгрузка следующей страницы (по 10 записей) при прокрутке — наблюдаем за
// пустым sentinel-элементом в конце ленты через IntersectionObserver, вместо
// того чтобы вручную считать scrollTop.
const sentinel = ref(null)
let observer = null
watch(sentinel, el => {
  observer?.disconnect()
  if (!el) return
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) fetchMore()
  }, { rootMargin: '400px' })
  observer.observe(el)
})
onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <div class="page">
    <div class="spacer" />

    <p v-if="loading" class="state-msg">Загрузка…</p>
    <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
    <p v-else-if="!city" class="state-msg">Город не найден.</p>

    <template v-else>
      <section class="hero">
        <div class="hero-bg" :style="{ backgroundImage: `url(${city.photo || fallbackHero})` }" />
        <div class="hero-overlay" />
        <div class="hero-content">
          <p class="hero-eyebrow">К 90-летию Кировской области · Исторические города</p>
          <h1 class="hero-title">{{ city.name }}</h1>
          <p v-if="city.short" class="hero-subtitle">{{ city.short }}</p>
        </div>
      </section>

      <nav class="breadcrumb">
        <RouterLink to="/">Главная</RouterLink><span>/</span>
        <RouterLink to="/cities">Исторические города</RouterLink><span>/</span>
        <span class="current">{{ city.name }}</span>
      </nav>

      <section v-if="city.description || city.eventsCount" class="info">
        <div class="info-grid">
          <div>
            <h2 class="info-heading">Общие сведения</h2>
            <p v-if="city.description">{{ city.description }}</p>
            <p v-else class="state-msg" style="padding:0; text-align:left;">Подробное описание города пока не добавлено.</p>
          </div>
          <div class="stats-col">
            <div class="stat">
              <strong class="stat-n">{{ city.eventsCount }}</strong>
              <span class="stat-label">событий в летописи города</span>
            </div>
          </div>
        </div>
      </section>

      <div class="back-bar">
        <RouterLink to="/cities" class="cta-btn">← К списку городов</RouterLink>
        <a v-if="editCityUrl" :href="editCityUrl" target="_blank" rel="noopener" class="edit-link">Редактировать</a>
      </div>

      <section class="timeline-section">
        <h2 class="section-heading">Лента времени</h2>
        <p class="section-sub">Ключевые события в истории города от первого упоминания до наших дней</p>

        <div class="timeline-controls">
          <div class="search-row">
            <div class="search-box">
              <svg viewBox="0 0 20 20" class="search-icon" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /></svg>
              <input
                type="search" class="search-input" placeholder="Поиск по названию и тексту события…"
                :value="historyTimelineState.search" @input="onSearchInput" aria-label="Поиск по названию и тексту события"
              />
            </div>
          </div>
          <FilterBar label="Век" :options="centuryOptions" :active="historyTimelineState.century" @select="selectCentury" />
        </div>

        <div ref="timelineTop">
          <p v-if="eventsLoading && !events.length" class="state-msg">Загрузка…</p>
          <p v-else-if="!total" class="state-msg">По выбранным условиям ничего не найдено.</p>
          <div v-else class="timeline">
            <div class="tl-line" />
            <div v-for="e in events" :key="e.id" class="tl-item">
              <div class="tl-dot" />
              <div class="tl-year">{{ e.dateText }}</div>
              <RouterLink :to="`/cities/${route.params.citySlug}/event/${e.id}`" class="tl-title">{{ e.title }}</RouterLink>
              <RouterLink :to="`/cities/${route.params.citySlug}/event/${e.id}`" class="tl-img-link">
                <div class="tl-img" :style="{ backgroundImage: `url(${eventImg(e)})` }" :aria-label="e.title" />
              </RouterLink>
            </div>
            <p v-if="loadingMore" class="state-msg tl-loading-more">Загрузка ещё…</p>
            <div ref="sentinel" class="tl-sentinel" aria-hidden="true" />
          </div>
        </div>

        <div v-if="total" class="timeline-controls timeline-controls--bottom">
          <FilterBar label="Век" :options="centuryOptions" :active="historyTimelineState.century" @select="selectCenturyFromBottom" />
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.state-msg { text-align: center; color: var(--color-muted); padding: var(--space-5) var(--space-3); }
.state-msg--error { color: var(--color-error, #b3261e); }
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
.stat { border: 1.5px dashed var(--color-border); border-radius: var(--radius); padding: var(--space-3); background: var(--color-surface); text-align: center }
.stat-n { display: block; font-size: 36px; font-family: var(--font-display); color: var(--color-teal); line-height: 1.2 }
.stat-label { font-size: 14px; color: var(--color-muted) }

.timeline-section { background: var(--color-birch); padding: var(--space-5) var(--space-3) }
.section-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); text-align: center; margin: 0 0 var(--space-2) }
.section-sub { text-align: center; font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-3) }
.timeline-controls { display: flex; flex-direction: column; align-items: center; gap: var(--space-2); margin-bottom: var(--space-4) }
.timeline-controls--bottom { margin-bottom: 0; margin-top: var(--space-4) }
.timeline-controls :deep(.filter-bar) { margin-bottom: 0; }
.search-row { display: flex; justify-content: center; width: 100%; }
.search-box {
  width: 100%; max-width: 360px; display: flex; align-items: center; gap: 8px;
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  padding: 0 12px; transition: border-color 150ms;
}
.search-box:focus-within { border-color: var(--color-ochre); }
.search-icon { width: 16px; height: 16px; flex-shrink: 0; fill: none; stroke: var(--color-muted); stroke-width: 1.8; stroke-linecap: round; }
.search-input { flex: 1; border: none; outline: none; background: none; font-family: var(--font-body); font-size: 14px; color: var(--color-ink); min-height: 44px; }
.search-input::placeholder { color: var(--color-muted); }
.timeline { position: relative; max-width: 800px; margin: 0 auto }
.tl-line { position: absolute; left: 19px; top: 0; bottom: 0; width: 2px; background: var(--color-ochre) }
.tl-item { position: relative; padding-left: 56px; margin-bottom: 32px }
.tl-dot { position: absolute; left: 12px; top: 6px; width: 16px; height: 16px; border-radius: 50%; background: var(--color-ochre); border: 3px solid var(--color-bg); box-shadow: 0 0 0 2px var(--color-ochre) }
.tl-year { font-family: var(--font-display); font-weight: 700; font-size: 22px; color: var(--color-ochre) }
.tl-title { display: block; font-size: 15px; margin-top: 4px; line-height: 1.6; color: var(--color-ink); text-decoration: none }
.tl-title:hover { color: var(--color-teal); text-decoration: underline; text-underline-offset: 3px }
.tl-img-link { display: block; margin-top: 8px; border-radius: var(--radius); overflow: hidden; max-width: 400px; border: 1.5px dashed var(--color-border); text-decoration: none; transition: border-color 300ms, box-shadow 300ms }
.tl-img-link:hover { border-color: var(--color-ochre); box-shadow: 0 4px 16px rgba(194,126,58,0.25) }
.tl-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center; filter: sepia(20%) }
.tl-loading-more { padding: var(--space-3) 0; }
.tl-sentinel { height: 1px }

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

.edit-link {
  display: inline-flex; align-items: center; min-height: 44px; padding: 10px 6px;
  font-family: var(--font-body); font-size: 12px; color: var(--color-muted);
  text-decoration: none; opacity: 0.65; transition: opacity 150ms, color 150ms;
}
.edit-link:hover { opacity: 1; color: var(--color-teal); text-decoration: underline; text-underline-offset: 3px; }

@media (max-width: 767px) {
  .info-grid { grid-template-columns: 1fr }
}
</style>
