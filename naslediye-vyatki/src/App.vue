<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SiteHeader from '@/layouts/SiteHeader.vue'
import SiteFooter from '@/layouts/SiteFooter.vue'

const route = useRoute()

const headerSolid = computed(() => route.meta.headerSolid ?? false)
</script>

<template>
  <SiteHeader :solid="headerSolid" />
  <main id="main-content">
    <router-view />
  </main>
  <SiteFooter />
</template>
