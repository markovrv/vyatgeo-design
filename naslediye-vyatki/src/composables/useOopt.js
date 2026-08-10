import { reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'
import { SITE_URL, decodeEntities, resolveRelativeUrls } from '@/composables/useHtmlContent'

export { SITE_URL }

export const natureCatalogState = reactive({
  district: [],
  profile: [],
  species: [],
  search: '',
  page: 1,
  perPage: 20,
  viewMode: 'map',
  scrollY: 0,
  filtersOpen: false,
})

function normalizeSite(raw) {
  const media = raw._embedded?.['wp:featuredmedia']?.[0]
  const sizes = media?.media_details?.sizes || {}
  const imgSrc = sizes.medium_large?.source_url
    || sizes.large?.source_url
    || sizes.medium?.source_url
    || sizes.full?.source_url
    || raw.oopt_imgSrc

  const coord = raw.oopt_coord || '0, 0'
  const parts = coord.split(',').map(s => parseFloat(s.trim()))

  const terms = raw._embedded?.['wp:term']?.flat() || []
  const districtTerms = terms.filter(t => t.taxonomy === 'oopt_district')
  const profileTerms = terms.filter(t => t.taxonomy === 'oopt_profile')
  const speciesTerms = terms.filter(t => t.taxonomy === 'oopt_species').map(t => ({
    id: t.id,
    slug: t.slug,
    name: t.name,
    status: t.meta?.oopt_species_status || '',
  }))

  return {
    id: raw.id,
    title: decodeEntities(raw.title?.rendered),
    place: districtTerms.map(t => t.name).join(', ') || '',
    coordinates: parts.length === 2 && !isNaN(parts[1]) ? [parts[1], parts[0]] : [0, 0],
    imgSrc,
    imgId: raw.featured_media || null,
    color: raw.oopt_color || '',
    summarize: decodeEntities(raw.oopt_summary || ''),
    area: raw.oopt_area || '',
    districtTerms,
    profileTerms,
    speciesTerms,
  }
}

export function useOoptSites() {
  const sites = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchSites() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/oopt?per_page=100&_embed=1`)
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

export function useOoptThumbnails() {
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

export function useOoptSite() {
  const site = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchSite(id) {
    loading.value = true
    error.value = null
    site.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/oopt/${encodeURIComponent(id)}?_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const raw = await res.json()

      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const sizes = media?.media_details?.sizes || {}
      const imgSrc = sizes.medium_large?.source_url || sizes.large?.source_url
        || sizes.medium?.source_url || sizes.full?.source_url
        || media?.source_url || raw.oopt_imgSrc || ''

      const terms = raw._embedded?.['wp:term']?.flat() || []
      const districtTerms = terms.filter(t => t.taxonomy === 'oopt_district')
      const profileTerms = terms.filter(t => t.taxonomy === 'oopt_profile')
      const speciesTerms = terms.filter(t => t.taxonomy === 'oopt_species').map(t => ({
        name: t.name,
        status: t.meta?.oopt_species_status || '',
      }))

      site.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: resolveRelativeUrls(raw.content?.rendered || ''),
        imgSrc,
        place: districtTerms.map(t => t.name).join(', ') || '',
        summarize: raw.oopt_summary || '',
        area: raw.oopt_area || '',
        districtTerms: districtTerms.map(t => ({ name: t.name, slug: t.slug })),
        profileTerms: profileTerms.map(t => ({ name: t.name, slug: t.slug })),
        speciesTerms,
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
  return { id: raw.id, title: decodeEntities(raw.title), place: decodeEntities(raw.place), imgSrc: raw.imgSrc || '', imgId: raw.img || null }
}

export function useOoptAdjacent() {
  const prev = ref(null)
  const next = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchAdjacent(id) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}oopt/v1/objects/${encodeURIComponent(id)}/adjacent`)
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

export function useOoptNearby() {
  const nearby = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchNearby(id, maxDistanceKm) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}oopt/v1/objects/${encodeURIComponent(id)}/nearby?max_distance=${encodeURIComponent(maxDistanceKm)}`)
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
