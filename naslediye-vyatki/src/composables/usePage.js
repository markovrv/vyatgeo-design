import { ref } from 'vue'
import { API_BASE_URL } from '@/config/api'
import { SITE_URL, decodeEntities, resolveRelativeUrls } from '@/composables/useHtmlContent'

export function usePage() {
  const page = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchPage(slug) {
    loading.value = true
    error.value = null
    page.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/pages?slug=${encodeURIComponent(slug)}&_embed=1`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      if (!data.length) {
        page.value = null
        return
      }
      const raw = data[0]
      // Фото hero — featured image страницы в исходном размере (не сжатая
      // WP-версия из media_details.sizes), если оно задано; фолбэк на
      // дефолтную картинку шаблона (см. PageView.vue) обрабатывается уже там.
      const media = raw._embedded?.['wp:featuredmedia']?.[0]
      const imgSrc = media?.source_url || ''

      page.value = {
        id: raw.id,
        title: decodeEntities(raw.title?.rendered),
        content: resolveRelativeUrls(raw.content?.rendered || ''),
        slug: raw.slug,
        imgSrc,
        commentStatus: raw.comment_status || 'closed',
      }
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { page, loading, error, fetchPage }
}
