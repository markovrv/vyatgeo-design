import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { API_BASE_URL } from '@/config/api'
import { SITE_URL } from '@/composables/useHtmlContent'

export function useMenu() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchMenu(location) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}nv/v1/menu/${encodeURIComponent(location)}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      items.value = (data || []).map(item => ({
        id: item.id,
        title: item.title,
        url: item.url,
        parentId: item.parentId,
        order: item.order,
        target: item.target || '',
      }))
    } catch (e) {
      error.value = e
      items.value = []
    } finally {
      loading.value = false
    }
  }

  return { items, loading, error, fetchMenu }
}

// Пункты меню, добавленные в WP через панель «Страницы»/«Записи» (а не
// «Произвольная ссылка»), приходят абсолютным URL текущего сайта
// (http://localhost:8080/test) — RouterLink :to ожидает путь, а не полный
// URL; переданный как есть, он склеивается с текущим origin в мусор вида
// "http://localhost:8080/http://localhost:8080/test". Отрезаем origin,
// оставляя путь+query+hash.
function toRelativePath(url) {
  try {
    const parsed = new URL(url, SITE_URL)
    return parsed.pathname + parsed.search + parsed.hash || '/'
  } catch {
    return url
  }
}

// Готовые к рендеру пункты меню (RouterLink/<a>, spaRoute/external) — общая
// логика для шапки и футера, чтобы не дублировать резолвинг WP-ссылок.
// hardcodedFallback — массив [key, label, to] на случай, если локация меню
// в WP ещё пуста (переходный период до заполнения редактором).
export function useResolvedNav(location, hardcodedFallback) {
  const router = useRouter()
  const { items: rawItems, loading, error, fetchMenu } = useMenu()

  const navItems = computed(() => {
    const wpItems = rawItems.value
      .filter(m => m.parentId === 0)
      .sort((a, b) => a.order - b.order)
    if (wpItems.length) {
      return wpItems.map(m => {
        const isOtherDomain = /^https?:\/\//i.test(m.url) && !m.url.startsWith(SITE_URL)
        if (isOtherDomain) {
          return { key: `wp-${m.id}`, label: m.title, to: m.url, external: true, spaRoute: false, target: m.target }
        }
        // Путь того же сайта — но это не значит, что у него есть клиентский
        // маршрут: сюда попадают и настоящие WP-пермалинки (/test — страница,
        // /archives/4986 — запись без своего SPA-компонента). Даём Vue Router
        // самому решить: если путь ни на что не матчится, ведём обычной
        // ссылкой <a> — полной навигацией на сервер, где WP уже резолвит
        // объект и 302-редиректит на нужный SPA-маршрут (см. nv_spa_bootstrap
        // в functions.php). RouterLink здесь непригоден именно потому, что
        // клиентская навигация не делает HTTP-запрос и до сервера не доходит.
        const path = toRelativePath(m.url)
        const spaRoute = router.resolve(path).matched.length > 0
        return { key: `wp-${m.id}`, label: m.title, to: path, external: false, spaRoute, target: m.target }
      })
    }
    return hardcodedFallback.map(([key, label, to]) => ({ key, label, to, external: false, spaRoute: true, target: '' }))
  })

  function load() {
    fetchMenu(location)
  }

  return { navItems, loading, error, load }
}
