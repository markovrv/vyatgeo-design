<script setup>
// Панель управления разделом «Лица Победы» — переключатель режима и поиск.
// Тот же компонент/паттерн, что и AttractionControls.vue (Архитектура):
// floating-плашка над картой в режиме карты, обычная строка над сеткой
// в режиме каталога — см. .controls/.controls--floating в HeroesView.vue.
import { computed } from 'vue'
import FilterBar from '@/components/FilterBar.vue'
import { useStrings } from '@/i18n'
import { heroesCatalogState as state } from '@/composables/useHeroes'

const { t } = useStrings()

const viewOptions = [
  { value: 'map', label: t('common.onMap') },
  { value: 'catalog', label: t('common.catalog') },
]

function onSearchInput(e) {
  state.search = e.target.value
  state.page = 1
}

// Кнопка «Фильтры» (статус + характер связи с краем) — в той же строке,
// справа от поиска, панель разворачивается в HeroesView.vue (тот же приём,
// что у NatureControls.vue/ArcheologyControls.vue).
const activeFilterCount = computed(() => state.status.length + state.connectionType.length)

function clearAllFilters() {
  state.status = []
  state.connectionType = []
  state.page = 1
}
</script>

<template>
  <div class="hero-controls">
    <FilterBar :options="viewOptions" :active="state.viewMode" @select="v => state.viewMode = v" />
    <div class="search-box">
      <svg viewBox="0 0 20 20" class="search-icon" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /></svg>
      <input
        type="search" class="search-input" :placeholder="t('components.HeroControls.searchPlaceholder')"
        :value="state.search" @input="onSearchInput" :aria-label="t('components.HeroControls.searchAria')"
      />
    </div>
    <button type="button" class="filters-toggle" @click="state.filtersOpen = !state.filtersOpen">
      <span>{{ t('components.HeroControls.filtersToggle') }}</span>
      <span v-if="activeFilterCount" class="filters-toggle-count">{{ activeFilterCount }}</span>
      <svg viewBox="0 0 20 20" class="filters-chevron" :class="{ 'filters-chevron--open': state.filtersOpen }"><polyline points="5 7.5 10 12.5 15 7.5" /></svg>
    </button>
    <button v-if="activeFilterCount" type="button" class="filters-reset" :title="t('components.HeroControls.filtersResetTitle')" @click="clearAllFilters">
      <svg viewBox="0 0 20 20" class="icon"><path d="M5 5l10 10M15 5 5 15" /></svg>
      <span>{{ t('common.reset') }}</span>
    </button>
  </div>
</template>

<style scoped>
.hero-controls { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: var(--space-2); }
.hero-controls :deep(.filter-bar) { margin-bottom: 0; }

.search-box {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  padding: 0 12px; transition: border-color 150ms;
}
.search-box:focus-within { border-color: var(--color-ochre); }
.search-icon { width: 16px; height: 16px; flex-shrink: 0; fill: none; stroke: var(--color-muted); stroke-width: 1.8; stroke-linecap: round; }
.search-input { flex: 1; border: none; outline: none; background: none; font-family: var(--font-body); font-size: 14px; color: var(--color-ink); min-height: 44px; }
.search-input::placeholder { color: var(--color-muted); }

.filters-toggle {
  display: flex; align-items: center; gap: 8px; white-space: nowrap; min-height: 44px;
  padding: 0 16px; font-family: var(--font-body); font-size: 13px; font-weight: 500; color: var(--color-ink);
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  cursor: pointer; transition: border-color 150ms;
}
.filters-toggle:hover { border-color: var(--color-ochre); }
.filters-toggle-count {
  font-size: 11px; font-weight: 700; background: var(--color-ochre); color: var(--color-bg);
  min-width: 18px; height: 18px; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 5px;
}
.filters-chevron { width: 14px; height: 14px; fill: none; stroke: var(--color-muted); stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; transition: transform 150ms; }
.filters-chevron--open { transform: rotate(180deg); }

.filters-reset {
  display: flex; align-items: center; gap: 6px; white-space: nowrap; min-height: 44px;
  padding: 0 14px; font-family: var(--font-body); font-size: 13px; color: var(--color-teal);
  background: none; border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  cursor: pointer; transition: border-color 150ms, color 150ms;
}
.filters-reset:hover { border-color: var(--color-ochre); color: var(--color-ochre); }
.filters-reset .icon { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }
</style>
