<script setup>
defineProps({
  img: { type: String, default: '' },
  tags: { type: Array, default: () => [] },
  title: { type: String, required: true },
  meta: { type: String, default: '' },
  desc: { type: String, default: '' },
  to: { type: String, required: true },
  aspectRatio: { type: String, default: '16/9' },
})
</script>

<template>
  <RouterLink :to="to" class="card">
    <div class="card-img" :style="{ backgroundImage: `url(${img})`, aspectRatio }" />
    <div class="card-body">
      <span v-for="t in tags" :key="t.label" class="tag" :class="`tag--${t.variant}`">{{ t.label }}</span>
      <h3 class="card-title">{{ title }}</h3>
      <p v-if="meta" class="card-meta">{{ meta }}</p>
      <p class="card-desc">{{ desc }}</p>
    </div>
  </RouterLink>
</template>

<style scoped>
.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.card-img { width: 100%; background-color: var(--color-birch); background-size: cover; background-position: center; }
.card-body { padding: var(--space-3); }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg); display: inline-block; margin-right: 6px; margin-bottom: 6px; }
.tag--teal { background: var(--color-teal); }
.tag--ochre { background: var(--color-ochre); }
.tag--muted { background: var(--color-muted); }
.card-title { font-family: var(--font-display); font-weight: 700; font-size: 18px; color: var(--color-ink); margin: 4px 0 4px; line-height: 1.3; }
.card-meta { font-size: 13px; color: var(--color-teal); letter-spacing: 0.04em; margin: 0 0 8px; }
.card-desc { font-size: 14px; color: var(--color-muted); line-height: 1.6; margin: 0; }
</style>
