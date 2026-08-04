import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/',                        name: 'home',                 component: () => import('@/views/IndexView.vue') },
  { path: '/victory',                 name: 'victory',              component: () => import('@/views/VictoryFacesView.vue') },
  { path: '/victory/:slug',           name: 'victory-person',       component: () => import('@/views/VictoryPersonView.vue'), meta: { headerSolid: true } },
  { path: '/ethnography',             name: 'ethnography',          component: () => import('@/views/EthnographyView.vue') },
  { path: '/ethnography/:slug',       name: 'ethnography-item',     component: () => import('@/views/EthnographyItemView.vue'), meta: { headerSolid: true } },
  { path: '/cities',                  name: 'cities',               component: () => import('@/views/HistoricCitiesView.vue') },
  { path: '/cities/:citySlug',        name: 'city-timeline',        component: () => import('@/views/CityTimelineView.vue') },
  { path: '/cities/:citySlug/event',  name: 'city-event',           component: () => import('@/views/CityEventView.vue'), meta: { headerSolid: true } },
  { path: '/nature',                  name: 'nature',               component: () => import('@/views/NatureView.vue') },
  { path: '/nature/:slug',            name: 'nature-site',          component: () => import('@/views/NatureSiteView.vue'), meta: { headerSolid: true } },
  { path: '/archeology',              name: 'archeology',           component: () => import('@/views/ArcheologyView.vue') },
  { path: '/archeology/:slug',        name: 'archeology-site',      component: () => import('@/views/ArcheologySiteView.vue'), meta: { headerSolid: true } },
  { path: '/architecture',            name: 'architecture',         component: () => import('@/views/ArchitectureView.vue') },
  { path: '/architecture/:slug',      name: 'architecture-object',  component: () => import('@/views/ArchitectureObjectView.vue'), meta: { headerSolid: true } },
  { path: '/about',                   name: 'about',                component: () => import('@/views/AboutView.vue') },
]

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 } },
})
