import { computed, reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'

export const SITE_URL = API_BASE_URL.replace(/wp-json\/?$/, '')

// Состояние ленты времени — как и в других модулях, живёт на уровне модуля,
// переживает переход на страницу события и обратно (фильтр по веку, scrollY).
export const historyTimelineState = reactive({
  citySlug: '',
  century: 'all',
  search: '',
  scrollY: 0,
})

const HISTORY_EVENTS_PER_PAGE = 10

// Уже подгруженные страницы событий текущего города/века/поиска — тоже на
// уровне модуля (как historyTimelineState), а не внутри composable: у Кирова
// уже 666 событий, лента грузится по 10 штук при прокрутке, и при возврате
// со страницы события пользователь должен увидеть все уже подгруженные
// ранее страницы, а не сброс к первым 10 записям.
const historyEventsCache = reactive({
  citySlug: '',
  century: 'all',
  search: '',
  events: [],
  page: 1,
  hasMore: true,
  total: 0,
  loaded: false,
})

// Раскрываем HTML entity-коды в заголовках/подписях, как и в остальных модулях —
// WP не разворачивает уже присутствующий в тексте entity-код сам.
const entityDecoder = typeof document !== 'undefined' ? document.createElement('textarea') : null
function decodeEntities(str) {
  if (!str || !entityDecoder) return str || ''
  entityDecoder.innerHTML = str
  return entityDecoder.value.replace(/\s+/g, ' ').trim()
}

// Контент авторился в расчёте на страницу двумя уровнями глубже корня сайта
// (../../wp-content/...) — как и в Архитектуре/Этнографии.
function resolveRelativeUrls(html) {
  return (html || '').replaceAll('../../', SITE_URL)
}

// Фото в тексте события свёрстаны как <center><a><img><br><i>подпись</i></a></center>
// перед остальным текстом абзаца — тот же почерк, что и в модуле "Архитектура"
// (см. useAttractions.js), поэтому используем идентичную логику разбора и
// вырезания, чтобы фото не дублировались (один раз в тексте, второй в карусели).
function extractGalleryAndCleanContent(html) {
  if (!html) return { gallery: [], cleanedHtml: html }
  if (typeof DOMParser === 'undefined') {
    return { gallery: [], cleanedHtml: html }
  }

  const doc = new DOMParser().parseFromString(html, 'text/html')
  const gallery = []
  const seen = new Set()
  const toRemove = new Set()

  for (const img of doc.body.querySelectorAll('img')) {
    const src = img.getAttribute('src')
    if (!src) continue
    const block = img.closest('center, p, figure, div, li') || img.parentElement

    let caption = ''
    let captionEl = null
    const inline = block.querySelector('i, em, figcaption')
    if (inline && inline.textContent.trim()) {
      caption = inline.textContent.trim()
    } else {
      for (let sib = block.nextElementSibling; sib; sib = sib.nextElementSibling) {
        if (sib.querySelector('img')) break
        const text = sib.textContent.trim()
        if (text) { caption = text; captionEl = sib; break }
      }
    }

    if (!seen.has(src)) {
      seen.add(src)
      gallery.push({ src, caption })
    }
    toRemove.add(block)
    if (captionEl) toRemove.add(captionEl)
  }

  toRemove.forEach(el => el.remove())
  doc.body.querySelectorAll('center, p').forEach(el => {
    if (!el.textContent.trim() && !el.querySelector('img, br')) el.remove()
  })
  return { gallery, cleanedHtml: doc.body.innerHTML }
}

function normalizeCity(raw) {
  return {
    id: raw.id,
    slug: raw.slug,
    name: decodeEntities(raw.name),
    description: decodeEntities(raw.description),
    short: decodeEntities(raw.short),
    photo: raw.photo || '',
    // Отдельная миниатюра 150×150 — специально для пина на карте (крупное
    // "photo" не помещается в круглый пин и не нужно там по размеру).
    photoThumb: raw.photoThumb || raw.photo || '',
    // API отдаёт [lat, lng]; Yandex Maps v3 (ymaps3) ждёт [lng, lat] — тот же
    // разворот, что и в useAttractions.js. null, если координаты не заданы —
    // тогда пин на карте просто не рисуется, а не улетает в [0, 0].
    coordinates: Array.isArray(raw.coordinates) && raw.coordinates.length === 2
      ? [raw.coordinates[1], raw.coordinates[0]]
      : null,
    eventsCount: raw.eventsCount || 0,
  }
}

// Все 7 городов проекта разом — список для страницы выбора города, включая
// те, у кого пока нет ни одного события (eventsCount === 0, показываем "скоро").
export function useHistoryCities() {
  const cities = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchCities() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}history/v1/cities`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      cities.value = (data || []).map(normalizeCity)
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { cities, loading, error, fetchCities }
}

// Один город по slug — для хиро на странице ленты времени. Городов всего 7,
// отдельный эндпоинт под один город не нужен — берём из общего списка.
export function useHistoryCity() {
  const city = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchCity(slug) {
    loading.value = true
    error.value = null
    city.value = null
    try {
      const res = await fetch(`${API_BASE_URL}history/v1/cities`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const raw = (data || []).find(c => c.slug === slug)
      city.value = raw ? normalizeCity(raw) : null
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { city, loading, error, fetchCity }
}

function normalizeEvent(raw) {
  const dateValue = raw.dateValue || ''
  return {
    id: raw.id,
    title: decodeEntities(raw.title),
    dateText: decodeEntities(raw.dateText),
    dateValue,
    year: dateValue ? parseInt(dateValue.slice(0, 4), 10) : null,
    imgSrc: raw.imgSrc || '',
    imgId: raw.img || null,
  }
}

async function fetchHistoryEventsPage(citySlug, century, search, page) {
  const params = new URLSearchParams({
    city: citySlug,
    page: String(page),
    per_page: String(HISTORY_EVENTS_PER_PAGE),
  })
  if (century && century !== 'all') params.set('century', century)
  if (search.trim()) params.set('search', search.trim())

  const res = await fetch(`${API_BASE_URL}history/v1/events?${params}`)
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  const data = await res.json()
  return {
    events: (data.events || []).map(normalizeEvent),
    pagination: data.pagination || { page, total: 0, totalPages: 0 },
  }
}

// Лента событий города — по 10 штук за раз (см. history/v1/events: у Кирова
// уже 666 записей, единым запросом грузить смысла нет). fetchEvents грузит
// первую страницу при первом открытии города/смене фильтра по веку/поиска
// (кэш уже загруженного — historyEventsCache, переиспользуется, пока не
// сменились город/век/поиск); fetchMore подгружает следующую страницу при
// прокрутке.
export function useHistoryEvents() {
  const loading = ref(false)
  const loadingMore = ref(false)
  const error = ref(null)

  async function fetchEvents(citySlug, century = 'all', search = '') {
    if (
      historyEventsCache.loaded
      && historyEventsCache.citySlug === citySlug
      && historyEventsCache.century === century
      && historyEventsCache.search === search
    ) {
      return
    }
    loading.value = true
    error.value = null
    try {
      const { events, pagination } = await fetchHistoryEventsPage(citySlug, century, search, 1)
      historyEventsCache.citySlug = citySlug
      historyEventsCache.century = century
      historyEventsCache.search = search
      historyEventsCache.events = events
      historyEventsCache.page = pagination.page || 1
      historyEventsCache.total = pagination.total || 0
      historyEventsCache.hasMore = (pagination.page || 1) < (pagination.totalPages || 1)
      historyEventsCache.loaded = true
    } catch (e) {
      error.value = e
      historyEventsCache.events = []
      historyEventsCache.hasMore = false
    } finally {
      loading.value = false
    }
  }

  async function fetchMore() {
    if (loadingMore.value || !historyEventsCache.hasMore) return
    loadingMore.value = true
    error.value = null
    try {
      const nextPage = historyEventsCache.page + 1
      const { events, pagination } = await fetchHistoryEventsPage(historyEventsCache.citySlug, historyEventsCache.century, historyEventsCache.search, nextPage)
      historyEventsCache.events = [...historyEventsCache.events, ...events]
      historyEventsCache.page = pagination.page || nextPage
      historyEventsCache.hasMore = (pagination.page || nextPage) < (pagination.totalPages || 1)
    } catch (e) {
      error.value = e
    } finally {
      loadingMore.value = false
    }
  }

  return {
    events: computed(() => historyEventsCache.events),
    total: computed(() => historyEventsCache.total),
    hasMore: computed(() => historyEventsCache.hasMore),
    loading,
    loadingMore,
    error,
    fetchEvents,
    fetchMore,
  }
}

const HISTORY_THUMBS_STORAGE_KEY = 'history_thumbnails_v1'

// В sessionStorage храним путь без домена — SITE_URL и так известен константой,
// дублировать его в каждой из сотен записей кэша незачем.
function toRelativeUrl(url) {
  return url && url.startsWith(SITE_URL) ? url.slice(SITE_URL.length) : url
}
function toAbsoluteUrl(path) {
  return path && !path.startsWith('http') ? `${SITE_URL}${path}` : path
}

function loadHistoryThumbsCache() {
  if (typeof sessionStorage === 'undefined') return {}
  try {
    const raw = JSON.parse(sessionStorage.getItem(HISTORY_THUMBS_STORAGE_KEY) || '{}')
    return Object.fromEntries(Object.entries(raw).map(([id, path]) => [id, toAbsoluteUrl(path)]))
  } catch {
    return {}
  }
}

function saveHistoryThumbsCache(cache) {
  if (typeof sessionStorage === 'undefined') return
  try {
    const compact = Object.fromEntries(Object.entries(cache).map(([id, url]) => [id, toRelativeUrl(url)]))
    sessionStorage.setItem(HISTORY_THUMBS_STORAGE_KEY, JSON.stringify(compact))
  } catch {
    // Приватный режим/переполненная квота — не критично, просто не кэшируем между сессиями.
  }
}

// Разрешённые URL миниатюр по id вложения — общий на весь модуль (а не
// локальный ref внутри composable) и зеркалируется в sessionStorage. Раньше
// это был ref внутри composable: при возврате на ленту после просмотра
// события компонент монтируется заново, ref создавался пустым, а watcher на
// events не срабатывал повторно (сам список событий брался из кэша и не
// менялся) — фотографии молча откатывались на миниатюру 150×150 из bulk-
// ответа. Теперь и сам справочник переживает переход на страницу события и
// обратно, и (за счёт sessionStorage) полную перезагрузку вкладки — без
// повторного батч-запроса к /wp/v2/media на уже разрешённые id.
const historyThumbnailsCache = ref(loadHistoryThumbsCache())

// history/v1/events отдаёт imgSrc только в размере WP-миниатюры 150×150 (та
// же, что и в остальных модулях) — батч-запрос за более крупным вариантом по
// id вложения, см. useAttractionThumbnails. Цепочка фолбэка обязана доходить
// до оригинала (medium_large → large → medium → full) — часть фото не имеет
// medium_large/large, если оригинал меньше их порога (см. CLAUDE.md).
export function useHistoryThumbnails() {
  async function fetchThumbnails(imgIds) {
    const ids = [...new Set(imgIds.filter(Boolean))].filter(id => !(id in historyThumbnailsCache.value))
    if (!ids.length) return
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/media?include=${ids.join(',')}&per_page=100&_fields=id,media_details`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const updates = {}
      for (const item of data || []) {
        const sizes = item.media_details?.sizes || {}
        updates[item.id] = sizes.medium_large?.source_url
          || sizes.large?.source_url
          || sizes.medium?.source_url
          || sizes.full?.source_url
          || null
      }
      historyThumbnailsCache.value = { ...historyThumbnailsCache.value, ...updates }
      saveHistoryThumbsCache(historyThumbnailsCache.value)
    } catch {
      // Не критично — карточки просто останутся с миниатюрой из массового ответа.
    }
  }

  return { thumbnails: historyThumbnailsCache, fetchThumbnails }
}

// Века, по которым выбираются вехи для блока на главной странице (см.
// useHistoryMilestones ниже) — те же 5 корзин, что и в фильтре ленты времени
// (ср. history_century_range на сервере).
const MILESTONE_CENTURY_RANGES = [
  { min: 1300, max: 1699 },
  { min: 1700, max: 1799 },
  { min: 1800, max: 1899 },
  { min: 1900, max: 1999 },
  { min: 2000, max: 2099 },
]

// Несколько реальных вех истории города для блока на главной странице
// ("Вятский край: семь веков истории", IndexView.vue) — раньше это был
// придуманный список дат, никак не связанный с реальными данными модуля
// "Города". Из каждого века — случайное реальное событие (разное при каждой
// загрузке страницы), а не произвольные выдуманные даты; каждая веха ведёт
// на настоящую страницу события. Год для подписи — ровно 4 цифры из
// dateValue (Y-m-d, всегда полная дата), а не dateText: тот заполняется
// вручную и может быть как "1780", так и "18 декабря 1780 г." — для единого
// вида на главной странице нужен just год.
export function useHistoryMilestones() {
  const milestones = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchMilestones(citySlug) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}history/v1/events?city=${encodeURIComponent(citySlug)}&per_page=1000`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const events = (data.events || []).map(normalizeEvent)

      milestones.value = MILESTONE_CENTURY_RANGES
        .map(range => events.filter(e => e.year != null && e.year >= range.min && e.year <= range.max))
        .filter(candidates => candidates.length)
        .map(candidates => candidates[Math.floor(Math.random() * candidates.length)])
        .map(e => ({ id: e.id, year: e.year, title: e.title }))
    } catch (e) {
      error.value = e
      milestones.value = []
    } finally {
      loading.value = false
    }
  }

  return { milestones, loading, error, fetchMilestones }
}

// Одно событие — для страницы события. history CPT зарегистрирован с
// show_in_rest, поэтому деталь идёт напрямую в стандартный /wp/v2/history/{id}
// (как useAttraction ходит в /wp/v2/attraction/{id}) — отдельный кастомный
// эндпоинт не нужен.
export function useHistoryEvent() {
  const event = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchEvent(id) {
    loading.value = true
    error.value = null
    event.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/history/${encodeURIComponent(id)}?_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const raw = await res.json()

      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const sizes = media?.media_details?.sizes || {}
      const imgSrc = sizes.medium_large?.source_url || sizes.large?.source_url || sizes.medium?.source_url || media?.source_url || ''

      const rawContent = resolveRelativeUrls(raw.content?.rendered || '')
      const { gallery, cleanedHtml } = extractGalleryAndCleanContent(rawContent)

      // _embedded['wp:term'] — массив массивов, один подмассив на таксономию;
      // у history зарегистрирована только "city", поэтому просто разворачиваем.
      const cityTerm = raw._embedded?.['wp:term']?.flat().find(t => t.taxonomy === 'city')

      event.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: cleanedHtml,
        images: gallery.length ? gallery.map(g => g.src) : (imgSrc ? [imgSrc] : []),
        gallery,
        imgSrc,
        dateText: decodeEntities(raw.history_date_text || ''),
        dateValue: raw.history_date_value || '',
        citySlug: cityTerm?.slug || '',
        cityName: cityTerm ? decodeEntities(cityTerm.name) : '',
      }
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { event, loading, error, fetchEvent }
}

function normalizeNavItem(raw) {
  if (!raw) return null
  return {
    id: raw.id,
    title: decodeEntities(raw.title),
    dateText: decodeEntities(raw.dateText),
    imgSrc: raw.imgSrc || '',
    imgId: raw.img || null,
  }
}

// Предыдущее/следующее событие по дате в рамках того же города.
export function useHistoryAdjacent() {
  const prev = ref(null)
  const next = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchAdjacent(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}history/v1/events/${encodeURIComponent(id)}/adjacent`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      prev.value = normalizeNavItem(data.prev)
      next.value = normalizeNavItem(data.next)
    } catch (e) {
      error.value = e
      prev.value = null
      next.value = null
    } finally {
      loading.value = false
    }
  }

  return { prev, next, loading, error, fetchAdjacent }
}

// Окно из до 5 событий (2 до, текущее, 2 после) по дате в рамках того же
// города — для мини-ленты навигации на странице события (аналог "ctln" из
// статического макета CityEventView.vue).
export function useHistoryNearby() {
  const nearby = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchNearby(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}history/v1/events/${encodeURIComponent(id)}/nearby`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      nearby.value = (data.nearby || []).map(item => ({ ...normalizeNavItem(item), active: !!item.active }))
    } catch (e) {
      error.value = e
      nearby.value = []
    } finally {
      loading.value = false
    }
  }

  return { nearby, loading, error, fetchNearby }
}
