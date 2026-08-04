<script setup>
defineProps({
  label: { type: String, default: '' },
  options: { type: Array, default: () => [] },
  active: { type: String, default: 'all' },
  pill: { type: Boolean, default: true },
})

const emit = defineEmits(['select'])

function chipStyle(opt, isActive, pill) {
  return {
    fontFamily: 'var(--font-body)',
    fontSize: '13px',
    padding: pill ? '8px 20px' : '8px 18px',
    background: isActive ? 'var(--color-ochre)' : 'var(--color-surface)',
    border: isActive ? '1.5px dashed var(--color-ochre)' : '1.5px dashed var(--color-border)',
    borderRadius: pill ? '999px' : 'var(--radius)',
    color: isActive ? 'var(--color-bg)' : 'var(--color-ink)',
    cursor: 'pointer',
    minHeight: '44px',
    transition: 'background 150ms, border-color 150ms',
  }
}
</script>

<template>
  <div class="filter-bar">
    <span v-if="label" class="filter-label">{{ label }}</span>
    <div class="filter-chips">
      <button
        v-for="chip in options" :key="chip.value"
        :style="chipStyle(chip, chip.value === active, pill)"
        @click="emit('select', chip.value)"
      >{{ chip.label }}</button>
    </div>
  </div>
</template>

<style scoped>
.filter-bar { margin-bottom: var(--space-2); }
.filter-label {
  display: block; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;
  color: var(--color-muted); text-align: center; margin-bottom: var(--space-1);
}
.filter-chips {
  display: flex; gap: var(--space-1); justify-content: center; flex-wrap: wrap;
}
</style>
