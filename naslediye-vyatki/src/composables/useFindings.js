import { reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'

export const SITE_URL = API_BASE_URL.replace(/wp-json\/?$/, '')

// Общее состояние каталога «Этнография» — вынесено на уровень модуля (singleton),
// поэтому переживает переход на страницу объекта и обратно: при возврате
// пользователь видит тот же фильтр, ту же страницу и ту же прокрутку,
// а не сброс к значениям по умолчанию.
export const ethnographyCatalogState = reactive({
  activeType: 'all',
  material: [],
  origin: [],
  creationTime: [],
  receiptTime: [],
  search: '',
  filtersOpen: false,
  page: 1,
  perPage: 20,
  scrollY: 0,
})

// Нормализует сырой ответ findings/v1/findings/ в удобную для вёрстки форму
function normalizeFinding(raw) {
  const images = raw.additional_images?.length
    ? raw.additional_images
    : (raw.thumbnail ? [raw.thumbnail] : [])

  return {
    id: raw.id,
    title: raw.title || '',
    content: raw.content || '',
    thumbnail: raw.thumbnail || '',
    images,
    catId: raw.cat_id || '',
    dimensions: raw.dimensions || '',
    functionality: raw.functionality || '',
    features: raw.features || '',
    materials: raw.materials || [],
    origin: raw.origin || [],
    creationTime: raw.creation_time || [],
    receiptTime: raw.receipt_time || [],
    types: raw.type || [],
  }
}

// Список находок — постранично, с фильтром по типу (slug таксономии finding_type).
// Ничего не грузит "на всякий случай": сколько запросили per_page, столько и придёт.
export function useFindings() {
  const findings = ref([])
  const total = ref(0)
  const totalPages = ref(0)
  const loading = ref(false)
  const error = ref(null)

  async function fetchFindings({
    page = 1, perPage = 20, typeSlug = '',
    material = [], origin = [], creationTime = [], receiptTime = [],
    search = '',
  } = {}) {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams({ page: String(page), per_page: String(perPage) })

      // WP отдаёт slug кириллических терминов уже percent-encoded (%d0%bf...).
      // Декодируем перед тем, как отдать URLSearchParams — иначе он закодирует
      // ещё раз и уйдёт двойной encoding (сервер тогда "случайно" сматчит верно
      // за счёт одного автоматического decode в PHP, но это хрупко).
      if (typeSlug) params.set('finding_type', decodeURIComponent(typeSlug))

      // Мультиселекты — массив slug'ов через повторяющийся ключ key[]=a&key[]=b,
      // PHP на сервере соберёт их обратно в массив для tax_query 'terms'.
      const appendMulti = (key, slugs) => {
        for (const s of slugs) params.append(`${key}[]`, decodeURIComponent(s))
      }
      appendMulti('finding_material', material)
      appendMulti('finding_origin', origin)
      appendMulti('finding_creation_time', creationTime)
      appendMulti('finding_receipt_time', receiptTime)

      if (search.trim()) params.set('search', search.trim())

      const res = await fetch(`${API_BASE_URL}findings/v1/findings/?${params}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      findings.value = (data.findings || []).map(normalizeFinding)
      total.value = data.total || 0
      totalPages.value = data.total_pages || 0
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { findings, total, totalPages, loading, error, fetchFindings }
}

// Одна находка по ID — для страницы объекта. Не тянет всю коллекцию.
export function useFinding() {
  const finding = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchFinding(id) {
    loading.value = true
    error.value = null
    finding.value = null
    try {
      const res = await fetch(`${API_BASE_URL}findings/v1/findings/?id=${encodeURIComponent(id)}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const raw = (data.findings || [])[0]
      finding.value = raw ? normalizeFinding(raw) : null
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { finding, loading, error, fetchFinding }
}

// Предыдущая/Следующая находка — опционально в рамках категории (typeSlug).
export function useFindingNav() {
  const prev = ref(null)
  const next = ref(null)
  const loading = ref(false)
  const error = ref(null)

  function normalizeNavItem(raw) {
    return raw ? { id: raw.id, title: raw.title || '', thumbnail: raw.thumbnail || '' } : null
  }

  async function fetchAdjacent(id, typeSlug = '') {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams()
      if (typeSlug) params.set('finding_type', decodeURIComponent(typeSlug))
      const res = await fetch(`${API_BASE_URL}findings/v1/findings/${encodeURIComponent(id)}/adjacent?${params}`)
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

// Похожие находки (до 4) — сервер сам ищет по совпадению таксономий и текста.
export function useFindingSimilar() {
  const similar = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchSimilar(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}findings/v1/findings/${encodeURIComponent(id)}/similar`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      similar.value = (data.similar || []).map(normalizeFinding)
    } catch (e) {
      error.value = e
      similar.value = []
    } finally {
      loading.value = false
    }
  }

  return { similar, loading, error, fetchSimilar }
}

export function useFindingTypes() {
  const types = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchFindingTypes() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/finding_type?per_page=100`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      types.value = data.map(t => ({
        id: t.id,
        name: t.name,
        slug: t.slug,
        image: t['finding_type-image'] || '',
        count: t.count || 0,
      }))
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { types, loading, error, fetchFindingTypes }
}

// Список терминов произвольной таксономии находок — для мультиселектов
// панели дополнительных фильтров (finding_material/origin/creation_time/receipt_time).
export function useTaxonomyOptions(taxonomy) {
  const options = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchOptions() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/${taxonomy}?per_page=100`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      options.value = data
        .map(t => ({ id: t.id, name: t.name, slug: t.slug, count: t.count || 0 }))
        .sort((a, b) => a.name.localeCompare(b.name, 'ru'))
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { options, loading, error, fetchOptions }
}
