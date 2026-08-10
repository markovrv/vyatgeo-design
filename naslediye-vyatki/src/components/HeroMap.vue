<script setup>
// Карта «Героев Вятского края» — приведена к дизайну карты «Архитектура»
// (AttractionMap.vue): кластеризация (та же круговая диаграмма-иконка),
// крупные фото-пины с ховер-карточкой вместо всегда видимой подписи,
// подсказка на маленьком зуме. Без исторических полигонов и попапа объекта
// (клик ведёт сразу на страницу героя, как и было) — у героев нет ни
// полигонов, ни своего попап-компонента, это не часть дизайна карты.
import { ref, shallowRef, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  YandexMap,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer,
  YandexMapControls,
  YandexMapGeolocationControl,
  YandexMapZoomControl,
  YandexMapScaleControl,
  YandexMapMarker,
  YandexMapClusterer,
  YandexMapHint,
  initYmaps,
} from 'vue-yandex-maps'
import AttractionClusterIcon from '@/components/AttractionClusterIcon.vue'
import { useStrings } from '@/i18n'

const { t } = useStrings()

const props = defineProps({
  points: { type: Array, required: true }, // [{ id, title, place, imgSrc, color, coordinates: [lng, lat] }]
})

const router = useRouter()
const map = shallowRef(null)
const zoom = ref(0)
const hoveredId = ref(null)

const mapSettings = {
  location: { center: [49.667, 58.603], zoom: 6 },
  zoomRange: { min: 5, max: 18 },
}

// z-index точек — при наведении точка поднимается выше соседей, иначе
// подсказка/фото прячется под другими пинами — тот же приём, что у
// AttractionMap.vue.
const zIndexMap = ref({})
const maxZIndex = computed(() => Math.max(0, ...Object.values(zIndexMap.value)))

function getZIndex(point, idx) {
  return zIndexMap.value[point.id] ?? idx
}
function bringToFront(point, idx) {
  if (getZIndex(point, idx) < maxZIndex.value) {
    zIndexMap.value = { ...zIndexMap.value, [point.id]: maxZIndex.value + 1 }
  }
}

function repoint(point, idx) {
  return {
    coordinates: point.coordinates,
    id: String(point.id),
    hideOutsideViewport: true,
    zIndex: getZIndex(point, idx),
    properties: { color: point.color, hint: point.title },
  }
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

function openSite(id) {
  router.push(`/victory/${id}`)
}

const apiReady = ref(false)

let zoomInterval = null
onMounted(async () => {
  await initYmaps()
  apiReady.value = true
  setTimeout(fitToPoints, 400)
  zoomInterval = setInterval(() => { zoom.value = map.value?.zoom ?? zoom.value }, 800)
})
onUnmounted(() => clearInterval(zoomInterval))
</script>

<template>
  <div class="hero-map">
    <p v-if="!apiReady" class="map-loading">{{ t('common.loadingMap') }}</p>
    <yandex-map v-else v-model="map" :settings="mapSettings" width="100%" height="560px">
      <yandex-map-default-scheme-layer />
      <yandex-map-default-features-layer />

      <yandex-map-controls :settings="{ position: 'left' }">
        <yandex-map-geolocation-control />
        <yandex-map-zoom-control />
      </yandex-map-controls>
      <yandex-map-controls :settings="{ position: 'bottom' }">
        <yandex-map-scale-control />
      </yandex-map-controls>

      <yandex-map-clusterer :settings="{ maxZoom: 18 }" :gridSize="128" zoom-on-cluster-click>
        <yandex-map-marker
          v-for="(point, idx) in points" :key="point.id"
          position="translate(-50%, -100%)"
          :settings="repoint(point, idx)"
        >
          <div class="pin" @mouseenter="hoveredId = point.id; bringToFront(point, idx)" @mouseleave="hoveredId = null" @click="openSite(point.id)">
            <div class="pin-photo" :style="{ backgroundImage: point.imgSrc ? `url(${point.imgSrc})` : 'none', borderColor: point.color || 'var(--color-ochre)' }" />
            <div class="pin-spike" :style="{ borderTopColor: point.color || 'var(--color-ochre)' }" />
            <transition name="fade">
              <div v-if="hoveredId === point.id && zoom > 13" class="pin-info">
                <div class="pin-title">{{ point.title }}</div>
                <div v-if="point.place" class="pin-subtitle">{{ point.place }}</div>
              </div>
            </transition>
          </div>
        </yandex-map-marker>

        <template #cluster="{ clusterer, coordinates }">
          <AttractionClusterIcon :colors="clusterer.features.map(f => f.properties.color || 'var(--color-ochre)')" :size="70" :key="coordinates.join('-')" />
        </template>
      </yandex-map-clusterer>

      <yandex-map-hint hint-property="hint" v-if="zoom <= 13">
        <template #default="{ content }">
          <div class="map-hint">{{ content }}</div>
        </template>
      </yandex-map-hint>
    </yandex-map>
    <p v-if="apiReady && !points.length" class="state-msg">{{ t('components.HeroMap.noPoints') }}</p>
  </div>
</template>

<style scoped>
.hero-map { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; }
.map-loading { text-align: center; color: var(--color-muted); padding: var(--space-5) 0; margin: 0; }
.state-msg { text-align: center; padding: var(--space-4); color: var(--color-muted); margin: 0; }

.pin { position: relative; cursor: pointer; display: flex; flex-direction: column; align-items: center; }
.pin-photo {
  width: 70px; height: 70px; border-radius: 50%; border: 4px solid var(--color-ochre);
  background-color: var(--color-birch); background-size: cover; background-position: center;
  box-shadow: 0 2px 10px rgba(42,33,24,0.4); transition: transform 150ms;
}
.pin:hover .pin-photo { transform: scale(1.08); }
.pin-spike {
  width: 0; height: 0; margin-top: -5px;
  border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 12px solid var(--color-ochre);
}
.pin-info {
  position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%);
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  box-shadow: var(--shadow-lg); padding: 6px 10px; white-space: nowrap; z-index: 10; pointer-events: none;
}
.pin-title { font-family: var(--font-display); font-weight: 700; font-size: 13px; color: var(--color-ink); }
.pin-subtitle { font-size: 11px; color: var(--color-muted); margin-top: 2px; }
.fade-enter-active, .fade-leave-active { transition: opacity 150ms; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.map-hint {
  background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius);
  padding: 4px 10px; font-family: var(--font-body); font-size: 12px; color: var(--color-ink);
  box-shadow: var(--shadow-sm); white-space: nowrap; transform: translate(10px, -50%);
}
</style>
