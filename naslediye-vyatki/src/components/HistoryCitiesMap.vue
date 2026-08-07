<script setup>
// Карта городов раздела «Исторические города» — упрощённая версия карты
// Архитектуры (AttractionMap.vue): без кластеризации (всего до 7 точек,
// разнесённых по всей области — накладываться им особо не на чем), без
// исторических границ и без попапа с галереей — клик по пину сразу ведёт
// на ленту времени города, как и клик по карточке в режиме каталога.
import { ref, shallowRef, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  YandexMap,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer,
  YandexMapControls,
  YandexMapZoomControl,
  YandexMapGeolocationControl,
  YandexMapMarker,
  initYmaps,
} from 'vue-yandex-maps'

const props = defineProps({
  points: { type: Array, required: true }, // [{ id, slug, name, photoThumb, coordinates: [lng, lat] }]
})

const router = useRouter()
const map = shallowRef(null)
const apiReady = ref(false)

// Стартовый центр — примерно середина Кировской области; после монтирования
// карта сама подстраивается под реально пришедшие точки через fitToPoints().
const mapSettings = {
  location: { center: [49.667, 58.603], zoom: 6 },
  zoomRange: { min: 5, max: 15 },
}

function fitToPoints() {
  if (!props.points.length) return
  if (props.points.length === 1) {
    map.value?.setLocation({ center: props.points[0].coordinates, zoom: 11, duration: 0 })
    return
  }
  const lngs = props.points.map(p => p.coordinates[0])
  const lats = props.points.map(p => p.coordinates[1])
  map.value?.setLocation({
    bounds: [
      [Math.min(...lngs), Math.min(...lats)],
      [Math.max(...lngs), Math.max(...lats)],
    ],
    duration: 0,
  })
}

function openCity(slug) {
  router.push(`/cities/${slug}`)
}

onMounted(async () => {
  await initYmaps()
  apiReady.value = true
  setTimeout(fitToPoints, 400)
})
</script>

<template>
  <div class="cities-map">
    <p v-if="!apiReady" class="map-loading">Загрузка карты…</p>
    <yandex-map v-else v-model="map" :settings="mapSettings" width="100%" height="600px">
      <yandex-map-default-scheme-layer />
      <yandex-map-default-features-layer />

      <yandex-map-controls :settings="{ position: 'right' }">
        <yandex-map-geolocation-control />
        <yandex-map-zoom-control />
      </yandex-map-controls>

      <yandex-map-marker
        v-for="point in points" :key="point.id"
        position="translate(-50%, -100%)"
        :settings="{ coordinates: point.coordinates, id: String(point.id) }"
      >
        <button type="button" class="pin" @click="openCity(point.slug)">
          <div class="pin-photo" :style="{ backgroundImage: point.photoThumb ? `url(${point.photoThumb})` : 'none' }" />
          <div class="pin-spike" />
          <span class="pin-label">{{ point.name }}</span>
        </button>
      </yandex-map-marker>
    </yandex-map>
  </div>
</template>

<style scoped>
.cities-map { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; }
.map-loading { text-align: center; color: var(--color-muted); padding: var(--space-5) 0; margin: 0; }

.pin {
  position: relative; cursor: pointer; display: flex; flex-direction: column; align-items: center;
  background: none; border: none; padding: 0; font: inherit;
}
.pin-photo {
  width: 56px; height: 56px; border-radius: 50%; border: 4px solid var(--color-ochre);
  background-color: var(--color-birch); background-size: cover; background-position: center;
  box-shadow: 0 2px 10px rgba(42,33,24,0.4); transition: transform 150ms;
}
.pin:hover .pin-photo { transform: scale(1.08); }
.pin-spike {
  width: 0; height: 0; margin-top: -5px;
  border-left: 7px solid transparent; border-right: 7px solid transparent; border-top: 10px solid var(--color-ochre);
}
.pin-label {
  margin-top: 4px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius);
  padding: 2px 8px; font-family: var(--font-body); font-size: 12px; font-weight: 500; color: var(--color-ink);
  white-space: nowrap; box-shadow: var(--shadow-sm);
}
</style>
