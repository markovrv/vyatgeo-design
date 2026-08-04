<script setup>
defineProps({ viewBox: { type: String, default: '0 0 800 600' } })
</script>

<template>
  <div class="map-wrap">
    <svg :viewBox="viewBox" class="map-svg" preserveAspectRatio="xMidYMid meet">
      <slot />
    </svg>
  </div>
</template>

<style scoped>
.map-wrap { max-width: 800px; margin: 0 auto; background: var(--color-birch); border-radius: var(--radius-lg); padding: var(--space-3) }
.map-svg { width: 100%; height: auto; display: block }
</style>
