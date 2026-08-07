<script setup>
import { ref, computed, onMounted } from 'vue'
import heroCities from '@/assets/img/historic-cities.jpg'
import CatalogLayout from '@/components/CatalogLayout.vue'
import FilterBar from '@/components/FilterBar.vue'
import HistoryCitiesMap from '@/components/HistoryCitiesMap.vue'
import { useHistoryCities } from '@/composables/useHistory'

const placeholderImg = heroCities
const { cities, loading, error, fetchCities } = useHistoryCities()
onMounted(fetchCities)

const viewMode = ref('map')
const viewOptions = [{ value: 'map', label: 'На карте' }, { value: 'catalog', label: 'Каталог' }]

// На карту попадают только города с заданными координатами (город без
// координат — только карточка в каталоге, пин рисовать не из чего).
const mapPoints = computed(() => cities.value
  .filter(c => c.coordinates)
  .map(c => ({ id: c.id, slug: c.slug, name: c.name, photoThumb: c.photoThumb, coordinates: c.coordinates })))
</script>

<template>
  <CatalogLayout
    :heroImg="heroCities"
    eyebrow="Исторические города · К 90-летию Кировской области"
    title="Исторические города Вятской земли"
    subtitle="Семь городов, каждый из которых хранит свою уникальную историю — от крепостей и купеческих центров до современных городов"
    :gridCount="cities.length"
    :showGrid="false"
  >
    <template #heading>Города на карте и в каталоге</template>
    <template #description>Семь исторических городов: Киров (Вятка / Хлынов), Котельнич, Орлов, Слободской, Яранск, Малмыж и Уржум. Нажмите на метку или карточку, чтобы открыть ленту времени города.</template>

    <template #map>
      <div class="map-shell">
        <div class="controls" :class="{ 'controls--floating': viewMode === 'map' }">
          <FilterBar :options="viewOptions" :active="viewMode" @select="v => viewMode = v" />
        </div>
        <template v-if="viewMode === 'map'">
          <p v-if="loading" class="state-msg">Загрузка…</p>
          <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
          <HistoryCitiesMap v-else :points="mapPoints" />
        </template>
      </div>
    </template>

    <template #grid>
      <template v-if="viewMode === 'catalog'">
        <p v-if="loading" class="state-msg">Загрузка…</p>
        <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
        <template v-else>
          <div class="grid-count">Показано: {{ cities.length }} городов</div>
          <template v-for="c in cities" :key="c.slug">
            <RouterLink v-if="c.eventsCount > 0" :to="`/cities/${c.slug}`" class="city-card">
              <div class="city-img" :style="{ backgroundImage: `url(${c.photo || placeholderImg})` }" />
              <div class="city-body">
                <h3 class="city-name">{{ c.name }}</h3>
                <p class="city-desc">{{ c.short }}</p>
                <span class="btn-sm">Лента времени →</span>
              </div>
            </RouterLink>
            <div v-else class="city-card city-card--soon">
              <div class="city-img" :style="{ backgroundImage: `url(${c.photo || placeholderImg})` }" />
              <div class="city-body">
                <h3 class="city-name">{{ c.name }}</h3>
                <p class="city-desc">{{ c.short }}</p>
                <span class="badge-soon">Скоро</span>
              </div>
            </div>
          </template>
        </template>
      </template>
    </template>
  </CatalogLayout>
</template>

<style scoped>
.state-msg { grid-column: 1 / -1; text-align: center; color: var(--color-muted); padding: var(--space-5) var(--space-3); }
.state-msg--error { color: var(--color-error, #b3261e); }

/* Панель управления — плавает над картой в режиме карты; в режиме каталога
   это обычная панель в потоке, сразу над списком карточек (тот же паттерн,
   что и в ArchitectureView.vue). Отступ от левого/правого края — одинаковые
   16px в обоих режимах, чтобы кнопки не «прыгали» по горизонтали. */
.map-shell { position: relative; margin-bottom: var(--space-3); }
.controls { margin: 0 16px var(--space-2); padding-top: 16px; }
.controls--floating {
  position: absolute; top: 16px; left: 16px; right: 16px; z-index: 20; margin: 0; padding-top: 0;
}

.grid-count { grid-column: 1 / -1; text-align: center; font-size: 13px; color: var(--color-muted); margin-bottom: var(--space-3) }
.btn-sm {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 500;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-bg);
  min-height: 44px;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
}
.city-card {
  display: block; background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden; text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out);
}
.city-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre) }
.city-card--soon { opacity: 0.6; filter: grayscale(40%); cursor: default }
.city-img { width: 100%; aspect-ratio: 16/9; background-color: var(--color-birch); background-size: cover; background-position: center }
.city-body { padding: var(--space-3) }
.city-name { font-family: var(--font-display); font-weight: 700; font-size: 20px; color: var(--color-ink); margin: 0 0 8px }
.city-desc { font-size: 14px; color: var(--color-muted); line-height: 1.6; margin: 0 0 var(--space-2) }
.badge-soon {
  display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; letter-spacing: 0.04em;
  text-transform: uppercase; color: var(--color-muted); border: 1.5px dashed var(--color-border);
  border-radius: 999px; padding: 4px 12px;
}
</style>
