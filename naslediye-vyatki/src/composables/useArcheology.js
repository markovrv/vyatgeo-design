import { reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'
import { SITE_URL, decodeEntities, resolveRelativeUrls } from '@/composables/useHtmlContent'

export { SITE_URL }

export const archeologyCatalogState = reactive({
  era: [],
  type: [],
  search: '',
  page: 1,
  perPage: 20,
  viewMode: 'map',
  scrollY: 0,
  filtersOpen: false,
})

const ERA_LABELS = {
  mezolit: 'Мезолит', neolit: 'Неолит', bronze: 'Эпоха бронзы',
  iron: 'Ранний железный век', medieval: 'Средневековье',
}
const TYPE_LABELS = {
  gorodishche: 'Городище', selishche: 'Селище', stoyanka: 'Стоянка',
  mogilnik: 'Могильник', klad: 'Клад',
}

function normalizeSite(raw) {
  return {
    id: raw.id,
    title: decodeEntities(raw.title),
    place: decodeEntities(raw.place),
    imgSrc: raw.imgSrc || '',
    imgId: raw.img || null,
    color: raw.color || '',
    era: raw.era || '',
    type: raw.type || '',
    eraLabel: ERA_LABELS[raw.era] || raw.era,
    typeLabel: TYPE_LABELS[raw.type] || raw.type,
    coordinates: Array.isArray(raw.coordinates) && raw.coordinates.length === 2
      ? [raw.coordinates[1], raw.coordinates[0]]
      : [0, 0],
  }
}

export function useArcheologySites() {
  const sites = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchSites() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}archeology/v1/objects`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      sites.value = (data || []).map(normalizeSite)
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { sites, loading, error, fetchSites }
}

export function useArcheologyThumbnails() {
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
        updates[item.id] = sizes.medium_large?.source_url
          || sizes.large?.source_url
          || sizes.medium?.source_url
          || sizes.full?.source_url
          || null
      }
      thumbnails.value = { ...thumbnails.value, ...updates }
    } catch {
      // Не критично
    }
  }

  return { thumbnails, fetchThumbnails }
}

function extractImages(html) {
  if (!html) return []
  const urls = [...html.matchAll(/<img[^>]+src=["']([^"']+)["']/g)].map(m => m[1])
  return [...new Set(urls)]
}

export function useArcheologySite() {
  const site = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchSite(id) {
    loading.value = true
    error.value = null
    site.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/archeology/${encodeURIComponent(id)}?_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const raw = await res.json()

      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const sizes = media?.media_details?.sizes || {}
      const imgSrc = sizes.medium_large?.source_url || sizes.large?.source_url || media?.source_url || raw.archeology_imgSrc || ''

      const rawContent = resolveRelativeUrls(raw.content?.rendered || '')
      const images = extractImages(rawContent)

      const eraTerms = raw._embedded?.['wp:term']?.flat().filter(t => t.taxonomy === 'archeology_era') || []
      const typeTerms = raw._embedded?.['wp:term']?.flat().filter(t => t.taxonomy === 'archeology_type') || []

      site.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: rawContent,
        images,
        place: decodeEntities(raw.archeology_place),
        summarize: raw.archeology_summarize || '',
        imgSrc,
        era: eraTerms[0]?.name || '',
        eraSlug: eraTerms[0]?.slug || '',
        type: typeTerms[0]?.name || '',
        typeSlug: typeTerms[0]?.slug || '',
        coord: raw.archeology_coord || '',
        commentStatus: raw.comment_status || 'closed',
      }
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { site, loading, error, fetchSite }
}

function normalizeNavItem(raw) {
  if (!raw) return null
  return {
    id: raw.id,
    title: decodeEntities(raw.title),
    place: decodeEntities(raw.place),
    imgSrc: raw.imgSrc || '',
    imgId: raw.img || null,
  }
}

export function useArcheologyAdjacent() {
  const prev = ref(null)
  const next = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchAdjacent(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}archeology/v1/objects/${encodeURIComponent(id)}/adjacent`)
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

export function useArcheologyNearby() {
  const nearby = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchNearby(id, maxDistanceKm) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}archeology/v1/objects/${encodeURIComponent(id)}/nearby?max_distance=${encodeURIComponent(maxDistanceKm)}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      nearby.value = (data.nearby || []).map(item => ({
        ...normalizeNavItem(item),
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
