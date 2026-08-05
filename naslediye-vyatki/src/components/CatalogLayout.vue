<script setup>
defineProps({
  heroImg: { type: String, required: true },
  eyebrow: { type: String, default: 'К 90-летию Кировской области' },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  showGrid: { type: Boolean, default: true },
  gridCount: { type: Number, default: 0 },
})
</script>

<template>
  <section class="hero">
    <div class="hero-bg" :style="{ backgroundImage: `url(${heroImg})` }" />
    <div class="hero-overlay" />
    <div class="hero-content">
      <p class="hero-eyebrow">{{ eyebrow }}</p>
      <h1 class="hero-title">{{ title }}</h1>
      <p v-if="subtitle" class="hero-subtitle">{{ subtitle }}</p>
    </div>
  </section>
  <section class="filters-area">
    <h2 class="filters-heading"><slot name="heading" /></h2>
    <p v-if="$slots.description" class="filters-desc"><slot name="description" /></p>
    <slot name="filters" />
  </section>
  <section class="content">
    <slot name="map" />
    <div v-if="showGrid && gridCount > 0" class="grid-count">Показано: {{ gridCount }}</div>
    <div class="grid"><slot name="grid" /></div>
    <slot name="pagination" />
  </section>
</template>

<style scoped>
.hero {
  position: relative; height: 60vh; min-height: 560px; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  margin-top: -80px; padding-top: 80px;
}
.hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; filter: sepia(50%) brightness(0.8); z-index: 0 }
.hero-overlay { position: absolute; inset: 0; z-index: 1; background: linear-gradient(to bottom, rgba(60,122,140,0.35), rgba(42,33,24,0.25)) }
.hero-content { position: relative; z-index: 2; text-align: center; max-width: 800px; padding: var(--space-5) var(--space-3) }
.hero-eyebrow { font-size: 13px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: var(--color-oak); margin: 0 0 var(--space-2); text-shadow: 0 2px 8px rgba(0,0,0,0.6) }
.hero-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(32px,4vw,52px); color: var(--color-bg); margin: 0 0 var(--space-2); text-shadow: 0 2px 12px rgba(0,0,0,0.45) }
.hero-subtitle { font-size: clamp(15px,1.4vw,18px); color: var(--color-oak); max-width: 600px; margin: 0 auto; text-shadow: 0 2px 10px rgba(0,0,0,0.5) }
.filters-area { padding: var(--space-5) var(--space-3) 0; max-width: 1200px; margin: 0 auto; text-align: center }
.filters-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); margin: 0 0 var(--space-2) }
.filters-desc { max-width: 800px; margin: 0 auto var(--space-3); line-height: 1.75 }
.content { padding: 0 var(--space-3) var(--space-5); max-width: 1200px; margin: 0 auto }
.grid-count { text-align: center; font-size: 13px; color: var(--color-muted); margin-bottom: var(--space-3) }
.grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: var(--space-3) }
</style>
