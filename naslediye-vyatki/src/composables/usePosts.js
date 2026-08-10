import { reactive, ref } from 'vue'
import { API_BASE_URL } from '@/config/api'
import { decodeEntities, resolveRelativeUrls, SITE_URL } from '@/composables/useHtmlContent'

export { SITE_URL }

export const postsCatalogState = reactive({
  search: '',
  page: 1,
  perPage: 10,
  scrollY: 0,
})

function normalizePost(raw) {
  const media = raw._embedded?.['wp:featuredmedia']?.[0]
  const sizes = media?.media_details?.sizes || {}
  const imgSrc = sizes.medium_large?.source_url
    || sizes.large?.source_url
    || sizes.medium?.source_url
    || media?.source_url
    || ''

  return {
    id: raw.id,
    title: decodeEntities(raw.title?.rendered),
    excerpt: decodeEntities(raw.excerpt?.rendered),
    date: raw.date || '',
    imgSrc,
    imgId: raw.featured_media || null,
  }
}

export function usePosts() {
  const posts = ref([])
  const total = ref(0)
  const totalPages = ref(0)
  const loading = ref(false)
  const error = ref(null)

  async function fetchPosts({ page = 1, perPage = 10, search = '' } = {}) {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams({ page: String(page), per_page: String(perPage), _embed: '1' })
      if (search.trim()) params.set('search', search.trim())
      const res = await fetch(`${API_BASE_URL}wp/v2/posts?${params}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      posts.value = (data || []).map(normalizePost)
      total.value = parseInt(res.headers.get('X-WP-Total') || '0', 10)
      totalPages.value = parseInt(res.headers.get('X-WP-TotalPages') || '1', 10)
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { posts, total, totalPages, loading, error, fetchPosts }
}

export function usePost() {
  const post = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchPost(id) {
    loading.value = true
    error.value = null
    post.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/posts/${encodeURIComponent(id)}?_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const raw = await res.json()

      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const sizes = media?.media_details?.sizes || {}
      const imgSrc = sizes.medium_large?.source_url
        || sizes.large?.source_url
        || sizes.medium?.source_url
        || media?.source_url
        || ''

      post.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: resolveRelativeUrls(raw.content?.rendered || ''),
        date: raw.date || '',
        imgSrc,
        commentStatus: raw.comment_status || 'closed',
      }
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { post, loading, error, fetchPost }
}
