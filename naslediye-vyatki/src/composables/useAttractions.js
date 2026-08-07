import { reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'

export const SITE_URL = API_BASE_URL.replace(/wp-json\/?$/, '')

// Общее состояние каталога «Архитектура Кирова» — как и в Этнографии, живёт на
// уровне модуля, переживает переход на страницу объекта и обратно.
export const attractionCatalogState = reactive({
  search: '',
  page: 1,
  perPage: 20,
  viewMode: 'map', // 'map' | 'catalog'
  scrollY: 0,
})

// У части находок (~35 из 423) заголовок в WP набран с буквальным HTML-кодом
// вместо символа (например "1870 &#8212; 1883 гг." вместо тире) — get_the_title()
// на сервере не разворачивает уже присутствующий в тексте entity-код, только
// добавляет типографику к обычным символам. Раскрываем сами на фронте, заодно
// схлопывая случайные переносы строк внутри заголовка.
const entityDecoder = typeof document !== 'undefined' ? document.createElement('textarea') : null
function decodeEntities(str) {
  if (!str || !entityDecoder) return str || ''
  entityDecoder.innerHTML = str
  return entityDecoder.value.replace(/\s+/g, ' ').trim()
}

// Контент авторился так, будто страница живёт на 2 уровня глубже корня сайта
// (../../wp-content/...) — так же, как в модулях "История"/"Этнография".
// Разворачиваем в абсолютные ссылки, иначе картинки в контенте не откроются.
function resolveRelativeUrls(html) {
  return (html || '').replaceAll('../../', SITE_URL)
}

function extractImages(html) {
  if (!html) return []
  const urls = [...html.matchAll(/<img[^>]+src=["']([^"']+)["']/g)].map(m => m[1])
  return [...new Set(urls)]
}

// Фото в тексте объекта обычно свёрстаны вручную одним из двух способов:
// 1) картинка и подпись (курсивом) внутри одной ссылки-обёртки center>a;
// 2) картинка в одной обёртке center, подпись — в следующей за ней center.
// Между блоками попадаются пустые center-обёртки — просто отступ, пропускаем.
// Разбираем через DOMParser (устойчивее регулярок к такой рукописной вёрстке),
// достаём пары {src, caption} и одновременно вырезаем эти блоки из текста —
// чтобы фото не дублировались (один раз в тексте, второй раз в карусели).
function extractGalleryAndCleanContent(html) {
  if (!html) return { gallery: [], cleanedHtml: html }
  if (typeof DOMParser === 'undefined') {
    return { gallery: extractImages(html).map(src => ({ src, caption: '' })), cleanedHtml: html }
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
        if (sib.querySelector('img')) break // это уже блок следующего фото — подписи нет
        const text = sib.textContent.trim()
        if (text) { caption = text; captionEl = sib; break }
        // пустой узел-разделитель — пропускаем, ищем дальше
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
  // После вырезания фото и подписей остаются пустые center/p-разделители
  // (в оригинале — просто межфотографный отступ) — без картинок внутри они
  // не нужны, а их margin в .content суммируется в заметный пустой хвост.
  doc.body.querySelectorAll('center, p').forEach(el => {
    if (!el.textContent.trim() && !el.querySelector('img, br')) el.remove()
  })
  return { gallery, cleanedHtml: doc.body.innerHTML }
}

function normalizeAttraction(raw) {
  return {
    id: raw.id,
    title: decodeEntities(raw.title),
    place: decodeEntities(raw.place),
    imgSrc: raw.imgSrc || '',
    imgId: raw.img || null,
    color: raw.color || '',
    // API отдаёт [lat, lng]; Yandex Maps v3 (ymaps3) ждёт [lng, lat] — разворачиваем сразу здесь,
    // чтобы всем остальным компонентам не приходилось об этом думать.
    coordinates: Array.isArray(raw.coordinates) && raw.coordinates.length === 2
      ? [raw.coordinates[1], raw.coordinates[0]]
      : [0, 0],
  }
}

// Все объекты разом — карта в любом случае требует полный набор точек в памяти
// одновременно (кластеризация не работает постранично), поэтому и каталожный
// режим просмотра фильтрует/пагинирует этот же массив на клиенте.
export function useAttractions() {
  const attractions = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchAttractions() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}attraction/v1/objects`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      attractions.value = (data || []).map(normalizeAttraction)
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { attractions, loading, error, fetchAttractions }
}

// attraction/v1/objects отдаёт imgSrc только в размере WP-миниатюры 150×150
// (её же используют мелкие пины карты) — для карточек каталога (от 300px
// шириной) это размывается. У сервера есть другие готовые варианты
// (medium/medium_large/large/full — WP генерирует их при загрузке), поэтому
// вместо растягивания миниатюры или скачивания оригинала целиком запрашиваем
// подходящий размер через стандартный /wp/v2/media по id вложения (поле
// `img` в массовом ответе) — одним батч-запросом на видимую страницу каталога.
export function useAttractionThumbnails() {
  const thumbnails = ref({})

  async function fetchThumbnails(imgIds) {
    const ids = [...new Set(imgIds.filter(Boolean))].filter(id => !(id in thumbnails.value))
    if (!ids.length) return
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/media?include=${ids.join(',')}&per_page=100&_fields=id,media_details`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const updates = {}
      for (const item of data || []) {
        const sizes = item.media_details?.sizes || {}
        // WP генерирует размер, только если оригинал больше его порога — у
        // небольших фото может не быть ни medium_large (768), ни large (1024).
        // Раньше в этом случае updates[id] уходил null и карточка молча
        // откатывалась на миниатюру 150×150 из массового ответа — вместо
        // этого перебираем оставшиеся варианты вплоть до оригинала (full).
        updates[item.id] = sizes.medium_large?.source_url
          || sizes.large?.source_url
          || sizes.medium?.source_url
          || sizes.full?.source_url
          || null
      }
      thumbnails.value = { ...thumbnails.value, ...updates }
    } catch {
      // Не критично — карточки просто останутся с миниатюрой из массового ответа.
    }
  }

  return { thumbnails, fetchThumbnails }
}

// Один объект — для страницы объекта. attraction/v1/objects намеренно не отдаёт
// summarize/content (см. api/docs/attraction.md), поэтому деталь всегда идёт
// отдельным запросом к стандартному /wp/v2/attraction/{id}.
export function useAttraction() {
  const attraction = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchAttraction(id) {
    loading.value = true
    error.value = null
    attraction.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/attraction/${encodeURIComponent(id)}?_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const raw = await res.json()
      // attraction_imgSrc — та же WP-миниатюра 150×150, что и в массовом
      // ответе (см. useAttractionThumbnails) — для крупного фото на странице
      // объекта берём готовый больший вариант из _embed, как и в каталоге.
      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const sizes = media?.media_details?.sizes || {}
      const imgSrc = sizes.medium_large?.source_url || sizes.large?.source_url || media?.source_url || raw.attraction_imgSrc || ''
      const coords = (raw.attraction_coord || '').split(',').map(s => parseFloat(s.trim()))
      const rawContent = resolveRelativeUrls(raw.content?.rendered || '')
      const { gallery, cleanedHtml } = extractGalleryAndCleanContent(rawContent)

      attraction.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: cleanedHtml,
        // Popup (AttractionMap) ждёт плоский массив URL — оставляем как есть,
        // с фолбэком на featured-фото, если в тексте вообще нет картинок.
        images: gallery.length ? gallery.map(g => g.src) : (imgSrc ? [imgSrc] : []),
        // Карусель на странице объекта показывает только реально найденные в
        // тексте фото с подписями — без фолбэка на imgSrc, чтобы не дублировать
        // его же в виде "галереи из одного фото" (imgSrc уже стоит во врезке).
        gallery,
        place: decodeEntities(raw.attraction_place),
        imgSrc,
        color: raw.attraction_color || '',
        summarize: raw.attraction_summarize || '',
        coordinates: coords.length === 2 && !coords.some(Number.isNaN) ? [coords[1], coords[0]] : null,
      }
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { attraction, loading, error, fetchAttraction }
}

function normalizeAttractionNavItem(raw) {
  if (!raw) return null
  return {
    id: raw.id,
    title: decodeEntities(raw.title),
    place: decodeEntities(raw.place),
    imgSrc: raw.imgSrc || '',
    imgId: raw.img || null,
  }
}

// Предыдущий/Следующий объект — по аналогии с Этнографией (useFindingNav),
// но без категорий: у attraction нет своих таксономий (см. api/docs/attraction.md).
export function useAttractionAdjacent() {
  const prev = ref(null)
  const next = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchAdjacent(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}attraction/v1/objects/${encodeURIComponent(id)}/adjacent`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      prev.value = normalizeAttractionNavItem(data.prev)
      next.value = normalizeAttractionNavItem(data.next)
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

// Ближайшие по координатам объекты ("Объекты рядом") — расстояние считает
// сервер (формула гаверсинуса), максимальный радиус поиска — параметр
// запроса maxDistanceKm, значение по умолчанию хранится константой на клиенте
// (ATTRACTION_NEARBY_RADIUS_KM, см. src/config/attraction.js), не на сервере.
export function useAttractionNearby() {
  const nearby = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchNearby(id, maxDistanceKm) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}attraction/v1/objects/${encodeURIComponent(id)}/nearby?max_distance=${encodeURIComponent(maxDistanceKm)}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      nearby.value = (data.nearby || []).map(item => ({
        ...normalizeAttractionNavItem(item),
        distanceKm: item.distanceKm ?? null,
      }))
    } catch (e) {
      error.value = e
      nearby.value = []
    } finally {
      loading.value = false
    }
  }

  return { nearby, loading, error, fetchNearby }
}
