<script setup>
// Карта объектов архитектуры — перенесена из старого прототипа
// (C:\...\attraction\vue\attraction-map), адаптирована:
// - без AI-пересказа (полагаемся на attraction_summarize как есть);
// - без дневного/ночного переключателя (можно добавить позже);
// - маркер/кластер оформлены в цветах и шрифтах дизайн-системы проекта;
// - клик по точке — всплывающее окно с описанием и галереей, как в оригинале.
import { ref, shallowRef, onMounted, onUnmounted, computed } from 'vue'
import {
  YandexMap,
  YandexMapDefaultSchemeLayer,
  YandexMapDefaultFeaturesLayer,
  YandexMapControls,
  YandexMapControlButton,
  YandexMapGeolocationControl,
  YandexMapZoomControl,
  YandexMapScaleControl,
  YandexMapMarker,
  YandexMapClusterer,
  YandexMapFeature,
  YandexMapHint,
  initYmaps,
} from 'vue-yandex-maps'
import AttractionClusterIcon from '@/components/AttractionClusterIcon.vue'
import AttractionPopup from '@/components/AttractionPopup.vue'
import { useAttraction } from '@/composables/useAttractions'

const props = defineProps({
  points: { type: Array, required: true }, // [{ id, title, place, imgSrc, color, coordinates: [lng, lat] }]
})

const map = shallowRef(null)
const zoom = ref(0)
const hoveredId = ref(null)

const mapSettings = {
  location: { center: [49.681453, 58.597582], zoom: 12 },
  zoomRange: { min: 11, max: 19 },
}

// Исторические границы города — 4 слоя, каждый в своём цвете дизайн-системы.
const POLYGON_LAYERS = [
  { file: 'Territoria_Khlynovskiy_Kreml.geojson', color: 'var(--color-ochre-dark, #8B5E2B)' },
  { file: 'Granitsy_posada_g_Khlynova_v_seredine_XVII_veka.geojson', color: 'var(--color-teal, #3C7A8C)' },
  { file: 'Rasprostranenie_territoriy_goroda_i_prilegayuschikh_dereven_k_kontsu_XVIII_v.geojson', color: '#8C7E6B' },
  { file: 'Granitsy_g_Vyatki_na_1812_g.geojson', color: 'var(--color-ochre, #C27E3A)' },
]
const showPolygons = ref(true)
const polygonFeatures = ref([])

async function loadPolygons() {
  const root = getComputedStyle(document.documentElement)
  const resolveColor = (v) => v.startsWith('var(')
    ? root.getPropertyValue(v.slice(4, -1).split(',')[0].trim()).trim() || v
    : v

  const loaded = await Promise.all(POLYGON_LAYERS.map(async (layer) => {
    const res = await fetch(`/polygons/${layer.file}`)
    const data = await res.json()
    const feature = data.features[0]
    const color = resolveColor(layer.color)
    return {
      geometry: feature.geometry,
      properties: feature.properties,
      style: {
        stroke: [{ width: 2, color }],
        fill: color + '22',
      },
    }
  }))
  polygonFeatures.value = loaded
}

// z-index точек — по умолчанию порядок в массиве, при наведении точка
// поднимается выше соседей (иначе подсказка/фото прячется под другими пинами,
// как было замечено). Перенесено из старого прототипа (easyShow/maxZindex).
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
  const lngs = props.points.map(p => p.coordinates[0])
  const lats = props.points.map(p => p.coordinates[1])
  map.value?.setLocation({
    bounds: [
      [Math.min(...lngs), Math.min(...lats)],
      [Math.max(...lngs), Math.max(...lats)],
    ],
  })
}

const { attraction: popupAttraction, loading: popupLoading, fetchAttraction } = useAttraction()
const popupOpen = ref(false)

function openObject(id) {
  popupOpen.value = true
  fetchAttraction(id)
}
function closePopup() {
  popupOpen.value = false
}

// vue-yandex-maps монтирует дочерние компоненты <yandex-map> (слои, кластерer)
// в тот же тик, что и сам <yandex-map>, не всегда дожидаясь полной готовности
// ymaps3 — из-за этого при первом монтировании возможна гонка. initYmaps() —
// собственная функция библиотеки для явного запуска/ожидания инициализации
// независимо от монтирования компонентов, рекомендуемый способ "delayed init".
const apiReady = ref(false)

let zoomInterval = null
onMounted(async () => {
  await initYmaps()
  apiReady.value = true
  loadPolygons()
  setTimeout(fitToPoints, 400)
  zoomInterval = setInterval(() => { zoom.value = map.value?.zoom ?? zoom.value }, 800)
})
onUnmounted(() => clearInterval(zoomInterval))
</script>

<template>
  <div class="attraction-map">
    <p v-if="!apiReady" class="map-loading">Загрузка карты…</p>
    <yandex-map v-else v-model="map" :settings="mapSettings" width="100%" height="600px">
      <yandex-map-default-scheme-layer />
      <yandex-map-default-features-layer />

      <yandex-map-controls :settings="{ position: 'left' }">
        <yandex-map-geolocation-control />
        <yandex-map-zoom-control />
        <yandex-map-control-button :settings="{ onClick: () => { showPolygons = !showPolygons } }">
          <span class="poly-toggle" :class="{ 'poly-toggle--active': showPolygons }" role="button" :aria-pressed="showPolygons">
            <svg viewBox="0 0 20 20" class="poly-icon" aria-hidden="true">
              <path d="M10 3.5 2.5 7.5 10 11.5l7.5-4z" />
              <path d="M2.5 10.5 10 14.5l7.5-4" />
              <path d="M2.5 13.5 10 17.5l7.5-4" />
            </svg>
            <span class="poly-tooltip">Исторические границы города</span>
          </span>
        </yandex-map-control-button>
      </yandex-map-controls>
      <yandex-map-controls :settings="{ position: 'bottom' }">
        <yandex-map-scale-control />
      </yandex-map-controls>

      <yandex-map-feature
        v-if="showPolygons"
        v-for="(feature, i) in polygonFeatures" :key="i"
        :settings="feature"
      />

      <yandex-map-clusterer :settings="{ maxZoom: 18 }" :gridSize="128" zoom-on-cluster-click>
        <yandex-map-marker
          v-for="(point, idx) in points" :key="point.id"
          position="translate(-50%, -100%)"
          :settings="repoint(point, idx)"
        >
          <div class="pin" @mouseenter="hoveredId = point.id; bringToFront(point, idx)" @mouseleave="hoveredId = null" @click="openObject(point.id)">
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

    <Teleport to="body">
      <AttractionPopup v-if="popupOpen" :attraction="popupAttraction" :loading="popupLoading" @close="closePopup" />
    </Teleport>
  </div>
</template>

<style scoped>
.attraction-map { border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; }
.map-loading { text-align: center; color: var(--color-muted); padding: var(--space-5) 0; margin: 0; }

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

.poly-toggle {
  /* Обёртка yandex-map-control-button добавляет свои ~8px паддинга с каждой
     стороны — 24px даёт итоговые 39.96×39.96, как у соседних кнопок карты. */
  position: relative; display: flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; color: var(--color-muted); cursor: pointer;
}
.poly-toggle--active { color: var(--color-ochre); }
.poly-icon { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
.poly-tooltip {
  position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%) translateX(-4px);
  background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius);
  padding: 5px 10px; font-family: var(--font-body); font-size: 12px; font-weight: 400; color: var(--color-ink);
  white-space: nowrap; box-shadow: var(--shadow-sm); opacity: 0; pointer-events: none;
  transition: opacity 150ms, transform 150ms; z-index: 5;
}
.poly-toggle:hover .poly-tooltip { opacity: 1; transform: translateY(-50%) translateX(0); }
</style>
