<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SiteHeader from '@/layouts/SiteHeader.vue'
import SiteFooter from '@/layouts/SiteFooter.vue'

const route = useRoute()

const navMap = {
  home: 'home', victory: 'victory', 'victory-person': 'victory',
  ethnography: 'ethnography', 'ethnography-item': 'ethnography',
  cities: 'cities', 'city-timeline': 'cities', 'city-event': 'cities',
  nature: 'nature', 'nature-site': 'nature',
  archeology: 'archeology', 'archeology-site': 'archeology',
  architecture: 'architecture', 'architecture-object': 'architecture',
  about: 'about',
}

const activeNav = computed(() => navMap[route.name] || 'home')
const headerSolid = computed(() => route.meta.headerSolid ?? false)
</script>

<template>
  <SiteHeader :active="activeNav" :solid="headerSolid" />
  <main id="main-content">
    <router-view />
  </main>
  <SiteFooter />
</template>
