<script setup>
// Панель управления разделом «Архитектура Кирова» — переключатель режима
// и поиск. Использует те же компонент/стили, что и панель фильтров
// Этнографии (FilterBar + .search-box), чтобы элементы управления были
// узнаваемы между разделами — здесь она просто плавает над картой в
// режиме карты, а в режиме каталога стоит обычной строкой над списком
// (см. ArchitectureView.vue).
import FilterBar from '@/components/FilterBar.vue'
import { attractionCatalogState as state } from '@/composables/useAttractions'

const viewOptions = [
  { value: 'map', label: 'На карте' },
  { value: 'catalog', label: 'Каталог' },
]

function onSearchInput(e) {
  state.search = e.target.value
  state.page = 1
}
</script>

<template>
  <div class="attraction-controls">
    <FilterBar :options="viewOptions" :active="state.viewMode" @select="v => state.viewMode = v" />
    <div class="search-box">
      <svg viewBox="0 0 20 20" class="search-icon" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /></svg>
      <input
        type="search" class="search-input" placeholder="Поиск по названию и месту расположения…"
        :value="state.search" @input="onSearchInput" aria-label="Поиск по названию и месту расположения"
      />
    </div>
  </div>
</template>

<style scoped>
.attraction-controls { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: var(--space-2); }
.attraction-controls :deep(.filter-bar) { margin-bottom: 0; }

.search-box {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  padding: 0 12px; transition: border-color 150ms;
}
.search-box:focus-within { border-color: var(--color-ochre); }
.search-icon { width: 16px; height: 16px; flex-shrink: 0; fill: none; stroke: var(--color-muted); stroke-width: 1.8; stroke-linecap: round; }
.search-input { flex: 1; border: none; outline: none; background: none; font-family: var(--font-body); font-size: 14px; color: var(--color-ink); min-height: 44px; }
.search-input::placeholder { color: var(--color-muted); }
</style>
