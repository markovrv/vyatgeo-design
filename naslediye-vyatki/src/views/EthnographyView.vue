<script setup>
import { onMounted, nextTick } from 'vue'
import { RouterLink, onBeforeRouteLeave } from 'vue-router'
import heroImg from '@/assets/img/ethnography.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import TypeFilterTiles from '@/components/TypeFilterTiles.vue'
import MultiSelectFilter from '@/components/MultiSelectFilter.vue'
import Pagination from '@/components/Pagination.vue'
import { useFindings, useFindingTypes, useTaxonomyOptions, ethnographyCatalogState as state } from '@/composables/useFindings'

const { findings, total, totalPages, loading, error, fetchFindings } = useFindings()
const { types, fetchFindingTypes } = useFindingTypes()

const material = useTaxonomyOptions('finding_material')
const origin = useTaxonomyOptions('finding_origin')
const creationTime = useTaxonomyOptions('finding_creation_time')
const receiptTime = useTaxonomyOptions('finding_receipt_time')

const activeFilterCount = () =>
  state.material.length + state.origin.length + state.creationTime.length + state.receiptTime.length

async function load() {
  await fetchFindings({
    page: state.page,
    perPage: state.perPage,
    typeSlug: state.activeType === 'all' ? '' : state.activeType,
    material: state.material,
    origin: state.origin,
    creationTime: state.creationTime,
    receiptTime: state.receiptTime,
    search: state.search,
  })
}

onMounted(async () => {
  // Дожидаемся всех запросов — иначе плитки/панель фильтров (выше сетки карточек)
  // могут ещё не отрисоваться в момент восстановления прокрутки, страница
  // окажется короче нужного, и scrollTo восстановит не ту позицию.
  await Promise.all([
    load(), fetchFindingTypes(),
    material.fetchOptions(), origin.fetchOptions(), creationTime.fetchOptions(), receiptTime.fetchOptions(),
  ])
  await nextTick()
  // nextTick гарантирует только то, что DOM пропатчен, но браузер может ещё
  // не успеть закончить layout/paint — scrollTo в этот момент получит "зажатую"
  // по текущей, ещё не окончательной высоте документа позицию. Ждём кадр отрисовки.
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))
  if (state.scrollY) window.scrollTo(0, state.scrollY)
})

// Запоминаем прокрутку прямо перед уходом со страницы (например, клик по
// карточке) — на неё же вернёмся при повторном заходе на каталог.
onBeforeRouteLeave(() => {
  state.scrollY = window.scrollY
})

function selectType(slug) {
  state.activeType = slug
  state.page = 1
  load()
}

function onMultiSelectChange(field, value) {
  state[field] = value
  state.page = 1
  load()
}

function clearAllFilters() {
  state.material = []
  state.origin = []
  state.creationTime = []
  state.receiptTime = []
  state.page = 1
  load()
}

let searchDebounce = null
function onSearchInput(e) {
  state.search = e.target.value
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    state.page = 1
    load()
  }, 400)
}

function changePage(p) {
  state.page = p
  load()
  window.scrollTo({ top: document.querySelector('.content')?.offsetTop - 100 || 0, behavior: 'smooth' })
}

function changePerPage(n) {
  state.perPage = n
  state.page = 1
  load()
}

function excerpt(text, len = 120) {
  if (!text) return ''
  const plain = text.replace(/<[^>]*>/g, '').trim()
  return plain.length > len ? plain.slice(0, len).trim() + '…' : plain
}
</script>

<template>
  <CatalogLayout
    :heroImg="heroImg"
    eyebrow="К 90-летию Кировской области · Этнографическая коллекция археологической лаборатории ВятГУ"
    title="Этнографическое наследие"
    subtitle="Коллекция предметов быта, костюмов, орудий труда и промыслов Вятского края"
    :gridCount="total"
  >
    <template #heading>Цифровой архив народной культуры</template>
    <template #description>Коллекция собрана экспедициями археологической лаборатории ВятГУ. Каждый экспонат сопровождается подробным описанием: материал, происхождение, время создания и поступления в фонд.</template>
    <template #filters>
      <TypeFilterTiles label="Категория экспоната" :types="types" :active="state.activeType" :allImage="heroImg" @select="selectType" />

      <div class="search-row">
        <div class="search-box">
          <svg viewBox="0 0 20 20" class="search-icon"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /></svg>
          <input
            type="search" class="search-input" placeholder="Поиск по названию и описанию…"
            :value="state.search" @input="onSearchInput"
          />
        </div>
        <button type="button" class="filters-toggle" @click="state.filtersOpen = !state.filtersOpen">
          <span>Дополнительные фильтры</span>
          <span v-if="activeFilterCount()" class="filters-toggle-count">{{ activeFilterCount() }}</span>
          <svg viewBox="0 0 20 20" class="filters-chevron" :class="{ 'filters-chevron--open': state.filtersOpen }"><polyline points="5 7.5 10 12.5 15 7.5" /></svg>
        </button>
        <button v-if="activeFilterCount()" type="button" class="filters-reset" title="Сбросить дополнительные фильтры" @click="clearAllFilters">
          <svg viewBox="0 0 20 20" class="icon"><path d="M5 5l10 10M15 5 5 15" /></svg>
          <span>Сбросить</span>
        </button>
      </div>

      <div v-if="state.filtersOpen" class="filters-panel">
        <MultiSelectFilter label="Материал" :options="material.options.value" :modelValue="state.material" @update:modelValue="v => onMultiSelectChange('material', v)" />
        <MultiSelectFilter label="Происхождение" :options="origin.options.value" :modelValue="state.origin" @update:modelValue="v => onMultiSelectChange('origin', v)" />
        <MultiSelectFilter label="Время создания" :options="creationTime.options.value" :modelValue="state.creationTime" @update:modelValue="v => onMultiSelectChange('creationTime', v)" />
        <MultiSelectFilter label="Время поступления" :options="receiptTime.options.value" :modelValue="state.receiptTime" @update:modelValue="v => onMultiSelectChange('receiptTime', v)" />
      </div>
    </template>
    <template #grid>
      <p v-if="loading" class="state-msg">Загрузка…</p>
      <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
      <p v-else-if="findings.length === 0" class="state-msg">По выбранному фильтру ничего не найдено.</p>
      <RouterLink v-for="f in findings" :key="f.id" :to="`/ethnography/${f.id}`" class="card">
        <div class="card-img" :style="{ backgroundImage: `url(${f.thumbnail})` }" role="img" :aria-label="f.title" />
        <div class="card-body">
          <div class="tags">
            <span v-if="f.catId" class="tag tag--muted">{{ f.catId }}</span>
            <span v-if="f.types[0]" class="tag tag--ochre">{{ f.types[0] }}</span>
          </div>
          <h3 class="card-title">{{ f.title }}</h3>
          <div v-if="f.materials.length" class="card-meta">{{ f.materials.join(', ') }}</div>
          <div v-if="f.creationTime.length" class="card-meta">{{ f.creationTime.join(', ') }}</div>
          <div v-if="f.origin.length" class="card-meta card-meta--muted">{{ f.origin.join(', ') }}</div>
          <p v-if="f.functionality || f.content" class="card-desc">{{ excerpt(f.functionality || f.content) }}</p>
        </div>
      </RouterLink>
    </template>
    <template #pagination>
      <Pagination
        :page="state.page" :totalPages="totalPages" :total="total" :perPage="state.perPage"
        @update:page="changePage" @update:perPage="changePerPage"
      />
    </template>
  </CatalogLayout>

  <section class="cta">
    <h2 class="cta-heading">Помогите сохранить этнографическое наследие</h2>
    <p class="cta-sub">Если у вас есть предметы народного быта, костюмы, фотографии или записи обрядов — свяжитесь с нами.</p>
    <button type="button" class="cta-btn">Связаться с проектом</button>
  </section>
</template>

<style scoped>
.cta { background: var(--color-birch); padding: var(--space-5) var(--space-3); text-align: center }
.cta-heading { font-family: var(--font-display); font-weight: 700; font-size: clamp(26px,3vw,36px); margin: 0 0 var(--space-2) }
.cta-sub { font-size: 16px; color: var(--color-teal); margin: 0 0 var(--space-3) }
.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 14px; font-weight: 500;
  padding: 14px 32px; border: none; cursor: pointer;
  background: var(--color-ochre); color: var(--color-bg); min-height: 44px;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms;
}
.cta-btn:hover { background: var(--color-teal) }
.state-msg { grid-column: 1 / -1; text-align: center; color: var(--color-muted); padding: var(--space-4) 0; }
.state-msg--error { color: var(--color-error, #b3261e); }
.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 4/3; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px }
.tag { font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.tag--muted { background: var(--color-muted) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 4px; color: var(--color-ink) }
.card-meta { font-size: 12px; color: var(--color-teal); margin-bottom: 2px }
.card-meta--muted { color: var(--color-muted); margin-bottom: 6px }
.card-desc { font-size: 13px; color: var(--color-ink); line-height: 1.5; margin: 6px 0 0 }

.search-row {
  display: flex; flex-wrap: wrap; gap: var(--space-2); align-items: stretch;
  margin-top: var(--space-3); max-width: 900px; margin-left: auto; margin-right: auto;
}
.search-box {
  flex: 1; min-width: 220px; display: flex; align-items: center; gap: 8px;
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  padding: 0 12px; transition: border-color 150ms;
}
.search-box:focus-within { border-color: var(--color-ochre); }
.search-icon { width: 16px; height: 16px; flex-shrink: 0; fill: none; stroke: var(--color-muted); stroke-width: 1.8; stroke-linecap: round; }
.search-input {
  flex: 1; border: none; outline: none; background: none; font-family: var(--font-body); font-size: 14px;
  color: var(--color-ink); min-height: 44px;
}
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

.filters-panel {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-2);
  max-width: 900px; margin: var(--space-2) auto 0; padding: var(--space-2);
  background: var(--color-birch); border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  align-items: start;
}
.filters-reset {
  display: flex; align-items: center; gap: 6px; white-space: nowrap; min-height: 44px;
  padding: 0 14px; font-family: var(--font-body); font-size: 13px; color: var(--color-teal);
  background: none; border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  cursor: pointer; transition: border-color 150ms, color 150ms;
}
.filters-reset:hover { border-color: var(--color-ochre); color: var(--color-ochre); }
.filters-reset .icon { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }

@media (max-width: 767px) {
  .search-row { flex-direction: column; }
}
</style>
