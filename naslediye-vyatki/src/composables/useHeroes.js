import { reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'
import { SITE_URL, decodeEntities, resolveRelativeUrls } from '@/composables/useHtmlContent'

export { SITE_URL }

export const heroesCatalogState = reactive({
  status: [],
  connectionType: [],
  search: '',
  page: 1,
  perPage: 20,
  viewMode: 'map',
  filtersOpen: false,
  scrollY: 0,
})

function normalizeHero(raw) {
  const media = raw._embedded?.['wp:featuredmedia']?.[0]
  const sizes = media?.media_details?.sizes || {}
  const imgSrc = sizes.medium_large?.source_url
    || sizes.large?.source_url
    || sizes.medium?.source_url
    || sizes.full?.source_url
    || raw.hero_imgSrc

  const coord = raw.hero_coord || '0, 0'
  const parts = coord.split(',').map(s => parseFloat(s.trim()))

  const terms = raw._embedded?.['wp:term']?.flat() || []
  const statusTerm = terms.find(t => t.taxonomy === 'hero_status')
  const connectionTypeTerms = terms.filter(t => t.taxonomy === 'hero_connection_type')

  return {
    id: raw.id,
    title: decodeEntities(raw.title?.rendered),
    place: decodeEntities(raw.hero_birth_place || ''),
    coordinates: parts.length === 2 && !isNaN(parts[1]) ? [parts[1], parts[0]] : [0, 0],
    imgSrc,
    imgId: raw.featured_media || null,
    color: raw.hero_color || '',
    summarize: decodeEntities(raw.hero_feat_description || ''),
    statusSlug: statusTerm?.slug || '',
    statusName: statusTerm?.name || '',
    connectionTypeTerms,
  }
}

export function useHeroes() {
  const heroes = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchHeroes() {
    loading.value = true
    error.value = null
    try {
      // WP REST ограничивает per_page максимумом 100 (rest_invalid_param при
      // большем значении) — 215 героев не помещаются в один запрос, нужна
      // постраничная подгрузка всех страниц и объединение результата.
      const perPage = 100
      const firstRes = await fetch(`${API_BASE_URL}wp/v2/hero?per_page=${perPage}&page=1&_embed=1`)
      if (!firstRes.ok) throw new Error(`HTTP ${firstRes.status}`)
      const totalPages = parseInt(firstRes.headers.get('X-WP-TotalPages') || '1', 10)
      let all = await firstRes.json()

      if (totalPages > 1) {
        const rest = await Promise.all(
          Array.from({ length: totalPages - 1 }, (_, i) => i + 2).map(async page => {
            const res = await fetch(`${API_BASE_URL}wp/v2/hero?per_page=${perPage}&page=${page}&_embed=1`)
            if (!res.ok) throw new Error(`HTTP ${res.status}`)
            return res.json()
          })
        )
        all = all.concat(...rest)
      }

      heroes.value = all.map(normalizeHero)
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { heroes, loading, error, fetchHeroes }
}

export function useHeroThumbnails() {
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
    } catch { /* не критично */ }
  }

  return { thumbnails, fetchThumbnails }
}

export function useHero() {
  const hero = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchHero(id) {
    loading.value = true
    error.value = null
    hero.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/hero/${encodeURIComponent(id)}?_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const raw = await res.json()

      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const sizes = media?.media_details?.sizes || {}
      const imgSrc = sizes.medium_large?.source_url || sizes.large?.source_url
        || sizes.medium?.source_url || sizes.full?.source_url
        || media?.source_url || raw.hero_imgSrc || ''

      const terms = raw._embedded?.['wp:term']?.flat() || []
      const statusTerm = terms.find(t => t.taxonomy === 'hero_status')

      hero.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: resolveRelativeUrls(raw.content?.rendered || ''),
        imgSrc,
        feat: raw.hero_feat_description || '',
        awards: raw.hero_awards || '',
        profession: raw.hero_profession || '',
        connection: raw.hero_connection || '',
        birthDate: raw.hero_birth_date || '',
        deathDate: raw.hero_death_date || '',
        birthPlace: raw.hero_birth_place || '',
        deathPlace: raw.hero_death_place || '',
        statusName: statusTerm?.name || '',
        statusSlug: statusTerm?.slug || '',
        commentStatus: raw.comment_status || 'closed',
      }
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { hero, loading, error, fetchHero }
}

function normalizeNavItem(raw) {
  if (!raw) return null
  return { id: raw.id, title: decodeEntities(raw.title), place: decodeEntities(raw.place), imgSrc: raw.imgSrc || '', imgId: raw.img || null }
}

export function useHeroAdjacent() {
  const prev = ref(null)
  const next = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchAdjacent(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}hero/v1/objects/${encodeURIComponent(id)}/adjacent`)
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

export function useHeroNearby() {
  const nearby = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchNearby(id, maxDistanceKm) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}hero/v1/objects/${encodeURIComponent(id)}/nearby?max_distance=${encodeURIComponent(maxDistanceKm)}`)
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
