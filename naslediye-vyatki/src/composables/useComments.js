import { ref } from 'vue'
import { API_BASE_URL, REST_NONCE } from '@/config/api'

export function useComments() {
  const comments = ref([])
  const loading = ref(false)
  const error = ref(null)
  const submitting = ref(false)
  const submitError = ref(null)
  const submitted = ref(false)

  async function fetchComments(postId) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/comments?post=${encodeURIComponent(postId)}&status=approve&order=asc&per_page=100`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      comments.value = (data || []).map(c => ({
        id: c.id,
        authorName: c.author_name,
        date: c.date,
        content: c.content?.rendered || '',
      }))
    } catch (e) {
      error.value = e
      comments.value = []
    } finally {
      loading.value = false
    }
  }

  async function postComment(postId, { authorName, authorEmail, content, parent = null }) {
    submitting.value = true
    submitError.value = null
    submitted.value = false
    try {
      const body = { post: postId, author_name: authorName, author_email: authorEmail, content }
      if (parent) body.parent = parent
      const headers = { 'Content-Type': 'application/json' }
      // Браузер и так шлёт куку сессии WP на этот же домен, если пользователь
      // залогинен, — без X-WP-Nonce WP не примет запись от такой "наполовину
      // опознанной" сессии (403), поэтому нужно передавать его каждый раз,
      // когда он есть, даже несмотря на то что автор передаётся текстом.
      if (REST_NONCE) headers['X-WP-Nonce'] = REST_NONCE
      const res = await fetch(`${API_BASE_URL}wp/v2/comments`, {
        method: 'POST',
        headers,
        credentials: 'include',
        body: JSON.stringify(body),
      })
      if (res.ok) {
        submitted.value = true
        return
      }
      if (res.status === 409) {
        submitError.value = 'duplicate'
        return
      }
      // Код ошибки нужно проверять раньше общей ветки по статусу: WP отдаёт
      // "закрыто у этой записи" (rest_comment_closed) тем же HTTP 403, что и
      // общий запрет анонимных комментариев — если проверять статус первым,
      // ветка 'closed' никогда не сработает и пользователь всегда увидит
      // обобщённое сообщение вместо точного «комментарии здесь закрыты».
      const data = await res.json().catch(() => ({}))
      if (data.code === 'rest_comment_closed') {
        submitError.value = 'closed'
      } else if (res.status === 401 || res.status === 403) {
        submitError.value = 'forbidden'
      } else {
        submitError.value = 'network'
      }
    } catch (e) {
      submitError.value = 'network'
    } finally {
      submitting.value = false
    }
  }

  return { comments, loading, error, submitting, submitError, submitted, fetchComments, postComment }
}

// Данные залогиненного в WP пользователя (имя + e-mail) для автоподстановки
// в форму комментариев — чтобы не заставлять его вводить то, что WP и так
// знает. /wp/v2/users/me без валидного X-WP-Nonce не распознаёт куку сессии
// вообще (отдаёт 401, как для анонима) — без REST_NONCE даже не пытаемся.
// context=edit обязателен: в контексте view WP не отдаёт email (приватное
// поле) даже для самого себя.
export function useCurrentUser() {
  const user = ref(null)
  const loading = ref(false)

  async function fetchCurrentUser() {
    if (!REST_NONCE) {
      user.value = null
      return
    }
    loading.value = true
    try {
      const res = await fetch(`${API_BASE_URL}wp/v2/users/me?context=edit`, {
        headers: { 'X-WP-Nonce': REST_NONCE },
        credentials: 'include',
      })
      if (!res.ok) { user.value = null; return }
      const data = await res.json()
      user.value = { name: data.name || '', email: data.email || '' }
    } catch {
      user.value = null
    } finally {
      loading.value = false
    }
  }

  return { user, loading, fetchCurrentUser }
}

// Автор-гость (не залогинен в WP) — имя/e-mail, однажды введённые в любую
// форму комментариев, запоминаются в localStorage и подставляются по
// умолчанию во все следующие формы на сайте, чтобы не вводить их заново на
// каждой странице. Только для гостей: если пользователь залогинен, поля и
// так приходят через useCurrentUser(), сохранять их локально незачем.
const GUEST_AUTHOR_KEY = 'nv_comment_author'

export function loadGuestAuthor() {
  try {
    const raw = localStorage.getItem(GUEST_AUTHOR_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return { name: parsed.name || '', email: parsed.email || '' }
  } catch {
    return null
  }
}

export function saveGuestAuthor(name, email) {
  try {
    localStorage.setItem(GUEST_AUTHOR_KEY, JSON.stringify({ name, email }))
  } catch { /* localStorage недоступен — не критично */ }
}
