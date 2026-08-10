import { createRouter, createWebHistory } from 'vue-router'

function getApiBaseUrl() {
  return window.__API_BASE_URL__ || 'https://testsite.vyatgeo.ru/wp-json/'
}

async function resolveSlugToId(cpt, slug) {
  try {
    const api = getApiBaseUrl()
    const res = await fetch(`${api}wp/v2/${cpt}/?slug=${encodeURIComponent(slug)}&_fields=id`)
    if (!res.ok) return null
    const data = await res.json()
    return data[0]?.id || null
  } catch {
    return null
  }
}

const routes = [
  { path: '/',                        name: 'home',                 component: () => import('@/views/IndexView.vue') },
  { path: '/victory',                 name: 'victory',              component: () => import('@/views/HeroesView.vue') },
  { path: '/victory/:id',             name: 'victory-person',       component: () => import('@/views/HeroItemView.vue'), meta: { headerSolid: true } },
  { path: '/ethnography',             name: 'ethnography',          component: () => import('@/views/EthnographyView.vue') },
  { path: '/ethnography/:id',         name: 'ethnography-item',     component: () => import('@/views/EthnographyItemView.vue'), meta: { headerSolid: true } },
  { path: '/cities',                  name: 'cities',               component: () => import('@/views/HistoricCitiesView.vue') },
  { path: '/cities/:citySlug',        name: 'city-timeline',        component: () => import('@/views/CityTimelineView.vue') },
  { path: '/cities/:citySlug/event/:id', name: 'city-event',        component: () => import('@/views/CityEventView.vue'), meta: { headerSolid: true } },
  { path: '/nature',                  name: 'nature',               component: () => import('@/views/NatureView.vue') },
  { path: '/nature/:id',              name: 'nature-site',          component: () => import('@/views/NatureItemView.vue'), meta: { headerSolid: true } },
  { path: '/archeology',              name: 'archeology',           component: () => import('@/views/ArcheologyView.vue') },
  { path: '/archeology/:id',          name: 'archeology-item',      component: () => import('@/views/ArcheologyItemView.vue'), meta: { headerSolid: true } },
  { path: '/architecture',            name: 'architecture',         component: () => import('@/views/ArchitectureView.vue') },
  { path: '/architecture/:id',        name: 'architecture-object',  component: () => import('@/views/ArchitectureObjectView.vue'), meta: { headerSolid: true } },
  // "О проекте" — обычная WP-страница (slug "about", см. nv_spa_ensure_about_page
  // в functions.php), рендерится тем же общим PageView.vue, что и /page/:slug —
  // просто slug зафиксирован пропсом маршрута, а не берётся из параметра.
  // headerSolid не ставим: PageView.vue сам рисует hero с тёмной подложкой
  // (margin-top: -80px, "уезжает под шапку" — как на / и /cities/:citySlug),
  // поверх которого шапка должна оставаться прозрачной до прокрутки.
  { path: '/about',                   name: 'about',                component: () => import('@/views/PageView.vue'), props: { slug: 'about' } },
  { path: '/news',                    name: 'news',                 component: () => import('@/views/NewsView.vue') },
  { path: '/news/:id',                name: 'news-item',            component: () => import('@/views/NewsItemView.vue'), meta: { headerSolid: true } },
  { path: '/page/:slug',              name: 'page',                 component: () => import('@/views/PageView.vue'), props: true },

  // Short-form /archives/* — матчим паттерн, но резолвим slug→ID на уровне
  // глобального beforeEach (ниже), потому что beforeEnter без component
  // не проходит в полный цикл навигации.
  { path: '/archives/finding/:slug' },
  { path: '/archives/attraction/:slug' },
  { path: '/archives/history/:slug' },
  { path: '/archives/city/:slug'      },
  { path: '/archives/archeology/:slug' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 }
  },
})

const moduleToCpt = { finding: 'finding', attraction: 'attraction', history: 'history', archeology: 'archeology' }
const moduleToRoute = { finding: 'ethnography', attraction: 'architecture', archeology: 'archeology' }

const ARCHIVES_RE = /^\/archives\/(finding|attraction|history|city|archeology)\/(.+)$/

router.beforeEach(async (to) => {
  const m = to.path.match(ARCHIVES_RE)
  if (!m) return

  const [, mod, slug] = m

  if (mod === 'city') return `/cities/${slug}`

  // mod здесь всегда 'finding'|'attraction'|'history' (city обработан выше,
  // остальные варианты ARCHIVES_RE не пропускает) — оба ключа есть в
  // moduleToCpt, поэтому cpt гарантированно определён.
  const cpt = moduleToCpt[mod]

  const id = await resolveSlugToId(cpt, slug)
  if (!id) return `/${moduleToRoute[mod] || 'cities'}`

  if (mod === 'history') {
    try {
      const api = getApiBaseUrl()
      const res = await fetch(`${api}wp/v2/history/${id}?_embed=1&_fields=id`)
      if (!res.ok) throw new Error()
      const raw = await res.json()
      const cityTerm = raw._embedded?.['wp:term']?.flat().find(t => t.taxonomy === 'city')
      return `/cities/${cityTerm?.slug || 'kirov'}/event/${id}`
    } catch {
      return `/cities/kirov/event/${id}`
    }
  }

  return `/${moduleToRoute[mod]}/${id}`
})

export default router
