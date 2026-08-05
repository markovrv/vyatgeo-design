<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  options: { type: Array, default: () => [] }, // [{ id, name, slug, count }]
  modelValue: { type: Array, default: () => [] }, // выбранные slug'и
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const rootEl = ref(null)

const selectedCount = computed(() => props.modelValue.length)

function isChecked(slug) {
  return props.modelValue.includes(slug)
}

function toggle(slug) {
  const next = isChecked(slug)
    ? props.modelValue.filter(s => s !== slug)
    : [...props.modelValue, slug]
  emit('update:modelValue', next)
}

function clear() {
  emit('update:modelValue', [])
}

function onDocClick(e) {
  if (rootEl.value && !rootEl.value.contains(e.target)) open.value = false
}
function onKeydown(e) {
  if (e.key === 'Escape') open.value = false
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKeydown)
})
onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="rootEl" class="ms-filter">
    <button type="button" class="ms-trigger" :class="{ 'ms-trigger--active': selectedCount > 0 }" @click="open = !open">
      <span class="ms-label">{{ label }}</span>
      <span v-if="selectedCount" class="ms-count">{{ selectedCount }}</span>
      <svg viewBox="0 0 20 20" class="ms-chevron" :class="{ 'ms-chevron--open': open }"><polyline points="5 7.5 10 12.5 15 7.5" /></svg>
    </button>

    <div v-if="open" class="ms-panel">
      <div v-if="selectedCount" class="ms-panel-header">
        <button type="button" class="ms-clear" @click="clear">Сбросить</button>
      </div>
      <p v-if="!options.length" class="ms-empty">Нет вариантов</p>
      <label v-for="opt in options" :key="opt.id" class="ms-option">
        <input type="checkbox" :checked="isChecked(opt.slug)" @change="toggle(opt.slug)" />
        <span class="ms-option-name">{{ opt.name }}</span>
        <span v-if="opt.count" class="ms-option-count">{{ opt.count }}</span>
      </label>
    </div>
  </div>
</template>

<style scoped>
.ms-filter { position: relative; }
.ms-trigger {
  display: flex; align-items: center; gap: 6px; width: 100%; min-height: 40px;
  padding: 8px 12px; font-family: var(--font-body); font-size: 13px; color: var(--color-ink);
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  cursor: pointer; transition: border-color 150ms;
}
.ms-trigger:hover, .ms-trigger--active { border-color: var(--color-ochre); }
.ms-label { flex: 1; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ms-count {
  flex-shrink: 0; font-size: 11px; font-weight: 700; background: var(--color-ochre); color: var(--color-bg);
  min-width: 18px; height: 18px; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 5px;
}
.ms-chevron { width: 14px; height: 14px; flex-shrink: 0; fill: none; stroke: var(--color-muted); stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; transition: transform 150ms; }
.ms-chevron--open { transform: rotate(180deg); }

.ms-panel {
  position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: var(--z-header, 1000);
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  box-shadow: var(--shadow-lg); max-height: 260px; overflow-y: auto; padding: 6px;
  min-width: 220px;
}
.ms-panel-header { display: flex; justify-content: flex-end; padding: 2px 6px 6px; border-bottom: 1px dashed var(--color-border); margin-bottom: 4px; }
.ms-clear { font-size: 12px; color: var(--color-teal); background: none; border: none; cursor: pointer; padding: 2px 4px; }
.ms-clear:hover { color: var(--color-ochre); }
.ms-empty { font-size: 13px; color: var(--color-muted); padding: 8px; margin: 0; }

.ms-option {
  display: flex; align-items: center; gap: 8px; padding: 7px 8px; border-radius: var(--radius);
  font-size: 13px; color: var(--color-ink); cursor: pointer; transition: background 150ms;
}
.ms-option:hover { background: var(--color-birch); }
.ms-option input[type="checkbox"] { flex-shrink: 0; width: 16px; height: 16px; accent-color: var(--color-ochre); cursor: pointer; }
.ms-option-name { flex: 1; line-height: 1.35; }
.ms-option-count { flex-shrink: 0; font-size: 11px; color: var(--color-muted); }
</style>
