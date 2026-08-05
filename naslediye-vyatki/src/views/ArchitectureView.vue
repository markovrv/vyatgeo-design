<script setup>
import { computed, nextTick, onMounted, watch } from 'vue'
import { RouterLink, onBeforeRouteLeave } from 'vue-router'
import heroImg from '@/assets/img/architecture-kirov.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import Pagination from '@/components/Pagination.vue'
import AttractionMap from '@/components/AttractionMap.vue'
import AttractionControls from '@/components/AttractionControls.vue'
import { useAttractions, useAttractionThumbnails, attractionCatalogState as state } from '@/composables/useAttractions'

const { attractions, loading, error, fetchAttractions } = useAttractions()
const { thumbnails, fetchThumbnails } = useAttractionThumbnails()

const filtered = computed(() => {
  const q = state.search.trim().toLowerCase()
  if (!q) return attractions.value
  return attractions.value.filter(a =>
    a.title.toLowerCase().includes(q) || a.place.toLowerCase().includes(q)
  )
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / state.perPage)))
const pageItems = computed(() => {
  const start = (state.page - 1) * state.perPage
  return filtered.value.slice(start, start + state.perPage)
})

// Карточки каталога (от 300px шириной) крупнее пинов карты и растягивают ту
// же WP-миниатюру 150×150 — подтягиваем более подходящий по размеру вариант
// (medium_large/large) для объектов видимой страницы, см. useAttractionThumbnails.
function cardImg(a) {
  return thumbnails.value[a.imgId] || a.imgSrc
}

watch([pageItems, () => state.viewMode], ([items, mode]) => {
  if (mode === 'catalog') fetchThumbnails(items.map(a => a.imgId))
}, { immediate: true })

function changePage(p) {
  state.page = p
  window.scrollTo({ top: document.querySelector('.content')?.offsetTop - 100 || 0, behavior: 'smooth' })
}

function changePerPage(n) {
  state.perPage = n
  state.page = 1
}

onMounted(async () => {
  await fetchAttractions()
  await nextTick()
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))
  if (state.scrollY) window.scrollTo(0, state.scrollY)
})

onBeforeRouteLeave(() => {
  state.scrollY = window.scrollY
})
</script>

<template>
  <CatalogLayout
    :heroImg="heroImg"
    eyebrow="К 90-летию Кировской области · От деревянного Хлынова до каменной Вятки"
    title="Архитектура Кирова"
    subtitle="Цифровой архив архитектурного облика областного центра — сохранившиеся и утраченные памятники зодчества и скульптуры"
    :gridCount="state.viewMode === 'catalog' ? filtered.length : 0"
  >
    <template #heading>Архитектурное наследие города</template>
    <template #description>Архитектурный облик Кирова (Вятки) складывался веками: от деревянных укреплений Хлынова до каменных особняков в стиле провинциального классицизма, купеческих домов в стиле модерн и советского конструктивизма.</template>

    <template #map>
      <div class="map-shell">
        <AttractionControls class="controls" :class="{ 'controls--floating': state.viewMode === 'map' }" />
        <template v-if="state.viewMode === 'map'">
          <p v-if="loading" class="state-msg">Загрузка карты…</p>
          <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
          <AttractionMap v-else :points="filtered" />
        </template>
      </div>
    </template>

    <template #grid>
      <template v-if="state.viewMode === 'catalog'">
        <p v-if="loading" class="state-msg">Загрузка…</p>
        <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
        <p v-else-if="pageItems.length === 0" class="state-msg">По запросу ничего не найдено.</p>
        <RouterLink v-for="a in pageItems" :key="a.id" :to="`/architecture/${a.id}`" class="card">
          <div class="card-img" :style="{ backgroundImage: `url(${cardImg(a)})` }" role="img" :aria-label="a.title" />
          <div class="card-body">
            <h3 class="card-title">{{ a.title }}</h3>
            <p v-if="a.place" class="card-meta">{{ a.place }}</p>
          </div>
        </RouterLink>
      </template>
    </template>

    <template v-if="state.viewMode === 'catalog'" #pagination>
      <Pagination
        :page="state.page" :totalPages="totalPages" :total="filtered.length" :perPage="state.perPage"
        @update:page="changePage" @update:perPage="changePerPage"
      />
    </template>
  </CatalogLayout>
</template>

<style scoped>
.state-msg { grid-column: 1 / -1; text-align: center; color: var(--color-muted); padding: var(--space-4) 0; }
.state-msg--error { color: var(--color-error, #b3261e); }
.card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.card-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.card-body { padding: var(--space-3) }
.card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin: 0 0 4px; color: var(--color-ink) }
.card-meta { font-size: 13px; color: var(--color-teal); margin: 0 }

/* Панель управления — плавает над картой в режиме карты; в режиме каталога
   это обычная панель в потоке, сразу над списком карточек. Отступ от
   левого/правого края — одинаковые 16px в обоих режимах, чтобы кнопки
   режима не «прыгали» по горизонтали при переключении, а поле поиска
   растягивалось до этого же отступа у правого края карты. */
.map-shell { position: relative; margin-bottom: var(--space-3); }
.controls { margin: 0 16px var(--space-2); padding-top: 16px; }
.controls--floating {
  position: absolute; top: 16px; left: 16px; right: 16px; z-index: 20; margin: 0; padding-top: 0;
}
</style>
