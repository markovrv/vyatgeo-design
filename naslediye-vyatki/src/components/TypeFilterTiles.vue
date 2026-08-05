<script setup>
const props = defineProps({
  label: { type: String, default: '' },
  types: { type: Array, default: () => [] }, // [{ id, name, image, count }]
  active: { type: String, default: 'all' },
  allImage: { type: String, default: '' },
})

const emit = defineEmits(['select'])

// Короткие подписи для плиток — полное название термина (для фильтрации и
// точности) остаётся в t.name и виден по наведению через title.
const SHORT_LABELS = {
  'Прядение, ткачество, шитьё': 'Текстиль',
  'Сельско-хозяйственные инструменты': 'Земледелие',
  'Плотницко-столярные инструменты': 'Столярство',
}

function shortLabel(name) {
  return SHORT_LABELS[name] || name
}
</script>

<template>
  <div class="type-tiles">
    <span v-if="label" class="tiles-label">{{ label }}</span>
    <div class="tiles-grid">
      <button
        type="button"
        class="tile"
        :class="{ 'tile--active': active === 'all' }"
        :aria-pressed="active === 'all'"
        @click="emit('select', 'all')"
      >
        <div class="tile-bg" :style="allImage ? { backgroundImage: `url(${allImage})` } : null" />
        <div class="tile-overlay" />
        <span v-if="active === 'all'" class="tile-check" aria-hidden="true">✓</span>
        <span class="tile-title">Все</span>
      </button>

      <button
        v-for="t in types" :key="t.id"
        type="button"
        class="tile"
        :class="{ 'tile--active': active === t.slug }"
        :aria-pressed="active === t.slug"
        :title="t.name"
        @click="emit('select', t.slug)"
      >
        <div class="tile-bg" :style="{ backgroundImage: `url(${t.image})` }" />
        <div class="tile-overlay" />
        <span v-if="active === t.slug" class="tile-check" aria-hidden="true">✓</span>
        <span v-if="t.count" class="tile-count">{{ t.count }}</span>
        <span class="tile-title">{{ shortLabel(t.name) }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.type-tiles { max-width: 900px; margin: 0 auto var(--space-3); }
.tiles-label {
  display: block; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;
  color: var(--color-muted); text-align: center; margin-bottom: var(--space-2);
}
.tiles-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-2);
}
@media (max-width: 1023px) {
  .tiles-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 767px) {
  .tiles-grid { grid-template-columns: repeat(2, 1fr); }
}
.tile {
  position: relative; height: 132px; overflow: hidden; border-radius: var(--radius);
  border: 1.5px solid var(--color-border); padding: 0; cursor: pointer; background: var(--color-birch);
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms;
}
.tile:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.tile--active { border-color: var(--color-ochre); box-shadow: 0 4px 16px rgba(42,33,24,0.15); }
.tile-bg {
  position: absolute; inset: 0; background-size: cover; background-position: center;
  filter: sepia(12%) saturate(1.1); transition: filter 300ms;
}
.tile:hover .tile-bg, .tile--active .tile-bg { filter: sepia(6%) saturate(1.15) brightness(1.03); }
.tile-overlay {
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 110% 90% at 0% 100%, rgba(42,33,24,0.92) 0%, rgba(42,33,24,0.55) 30%, rgba(42,33,24,0) 62%);
}
.tile-title {
  position: absolute; left: 10px; right: 10px; bottom: 10px; z-index: 1;
  font-family: var(--font-display); font-weight: 700; font-size: 15px; line-height: 1.25;
  color: var(--color-bg); text-align: left; text-shadow: 0 1px 5px rgba(0,0,0,0.85);
}
.tile-count {
  position: absolute; top: 8px; right: 8px; z-index: 1; font-size: 11px; font-weight: 500;
  background: var(--color-teal); color: var(--color-bg); padding: 2px 9px; border-radius: 999px;
}
.tile-check {
  position: absolute; top: 8px; left: 8px; z-index: 1; width: 20px; height: 20px;
  display: flex; align-items: center; justify-content: center; border-radius: 50%;
  background: var(--color-ochre); color: var(--color-bg); font-size: 12px; font-weight: 700;
}
</style>
