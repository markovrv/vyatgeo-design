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
  { path: '/victory',                 name: 'victory',              component: () => import('@/views/VictoryFacesView.vue') },
  { path: '/victory/:slug',           name: 'victory-person',       component: () => import('@/views/VictoryPersonView.vue'), meta: { headerSolid: true } },
  { path: '/ethnography',             name: 'ethnography',          component: () => import('@/views/EthnographyView.vue') },
  { path: '/ethnography/:id',         name: 'ethnography-item',     component: () => import('@/views/EthnographyItemView.vue'), meta: { headerSolid: true } },
  { path: '/cities',                  name: 'cities',               component: () => import('@/views/HistoricCitiesView.vue') },
  { path: '/cities/:citySlug',        name: 'city-timeline',        component: () => import('@/views/CityTimelineView.vue') },
  { path: '/cities/:citySlug/event/:id', name: 'city-event',        component: () => import('@/views/CityEventView.vue'), meta: { headerSolid: true } },
  { path: '/nature',                  name: 'nature',               component: () => import('@/views/NatureView.vue') },
  { path: '/nature/:slug',            name: 'nature-site',          component: () => import('@/views/NatureSiteView.vue'), meta: { headerSolid: true } },
  { path: '/archeology',              name: 'archeology',           component: () => import('@/views/ArcheologyView.vue') },
  { path: '/archeology/:slug',        name: 'archeology-site',      component: () => import('@/views/ArcheologySiteView.vue'), meta: { headerSolid: true } },
  { path: '/architecture',            name: 'architecture',         component: () => import('@/views/ArchitectureView.vue') },
  { path: '/architecture/:id',        name: 'architecture-object',  component: () => import('@/views/ArchitectureObjectView.vue'), meta: { headerSolid: true } },
  { path: '/about',                   name: 'about',                component: () => import('@/views/AboutView.vue') },

  // Short-form /archives/* — матчим паттерн, но резолвим slug→ID на уровне
  // глобального beforeEach (ниже), потому что beforeEnter без component
  // не проходит в полный цикл навигации.
  { path: '/archives/finding/:slug' },
  { path: '/archives/attraction/:slug' },
  { path: '/archives/history/:slug' },
  { path: '/archives/city/:slug'      },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 }
  },
})

const moduleToCpt = { finding: 'finding', attraction: 'attraction', history: 'history' }
const moduleToRoute = { finding: 'ethnography', attraction: 'architecture' }

const ARCHIVES_RE = /^\/archives\/(finding|attraction|history|city)\/(.+)$/

router.beforeEach(async (to) => {
  const m = to.path.match(ARCHIVES_RE)
  if (!m) return

  const [, mod, slug] = m

  if (mod === 'city') return `/cities/${slug}`

  const cpt = moduleToCpt[mod]
  if (!cpt) return '/'

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
