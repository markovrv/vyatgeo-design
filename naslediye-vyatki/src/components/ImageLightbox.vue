<script setup>
// Полноэкранный просмотр изображений — перенесённая на Vue версия логики
// из api/artifact-finder/included/js/finding-gallery.js (оригинальный скрипт
// плагина): зум колесиком/кнопками, перетаскивание при увеличении, яркость/
// контраст, навигация между фото, миниатюры, клавиатура, настройки в localStorage.
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  images: { type: Array, required: true },
  initialIndex: { type: Number, default: 0 },
  alt: { type: String, default: '' },
})

const emit = defineEmits(['close', 'change-index'])

const index = ref(props.initialIndex)
const scale = ref(1)
const brightness = ref(100)
const contrast = ref(100)
const zoomEnabled = ref(true)
const notification = ref('')
let notificationTimer = null

const wrapperEl = ref(null)
const imgEl = ref(null)
const isDragging = ref(false)
let dragStartX = 0, dragStartY = 0, scrollStartLeft = 0, scrollStartTop = 0

const SCALE_STEP = 0.2
const SCALE_MIN = 0.2
const SCALE_MAX = 5

const current = computed(() => props.images[index.value])
const filterCss = computed(() => `brightness(${brightness.value}%) contrast(${contrast.value}%)`)

// Реальный размер картинки "по размеру экрана" (как раньше давал CSS
// object-fit: contain) — считаем сами в пикселях и от него уже масштабируем,
// вместо object-fit + transform: scale(). object-fit не меняет layout-размер
// элемента, поэтому в момент scale>1 картинка "прыгала" на свой натуральный
// (часто в разы больший) пиксельный размер — отсюда рывок при первом же
// шаге зума.
const fitWidth = ref(0)
const fitHeight = ref(0)

function computeFitSize() {
  const img = imgEl.value
  const wrapper = wrapperEl.value
  if (!img || !wrapper || !img.naturalWidth || !img.naturalHeight) return
  const wrapperRatio = wrapper.clientWidth / wrapper.clientHeight
  const imgRatio = img.naturalWidth / img.naturalHeight
  if (imgRatio > wrapperRatio) {
    fitWidth.value = wrapper.clientWidth
    fitHeight.value = wrapper.clientWidth / imgRatio
  } else {
    fitHeight.value = wrapper.clientHeight
    fitWidth.value = wrapper.clientHeight * imgRatio
  }
}

const displayWidth = computed(() => fitWidth.value ? fitWidth.value * scale.value : null)
const displayHeight = computed(() => fitHeight.value ? fitHeight.value * scale.value : null)

function loadSettings() {
  try {
    const b = localStorage.getItem('findingGallery_brightness')
    const c = localStorage.getItem('findingGallery_contrast')
    const z = localStorage.getItem('findingGallery_zoomEnabled')
    if (b) brightness.value = parseInt(b)
    if (c) contrast.value = parseInt(c)
    if (z) zoomEnabled.value = z === 'true'
  } catch { /* localStorage недоступен — работаем со значениями по умолчанию */ }
}

function saveSettings() {
  try {
    localStorage.setItem('findingGallery_brightness', brightness.value)
    localStorage.setItem('findingGallery_contrast', contrast.value)
    localStorage.setItem('findingGallery_zoomEnabled', zoomEnabled.value)
  } catch { /* некуда сохранять — не критично */ }
}

function notify(message) {
  notification.value = message
  clearTimeout(notificationTimer)
  notificationTimer = setTimeout(() => { notification.value = '' }, 1000)
}

function resetZoom() {
  scale.value = 1
  if (wrapperEl.value) {
    wrapperEl.value.scrollLeft = 0
    wrapperEl.value.scrollTop = 0
  }
}

function clampScale(next) { return Math.max(SCALE_MIN, Math.min(SCALE_MAX, +next.toFixed(2))) }

// Масштабирование "от точки": точка контента под (pointerX, pointerY)
// остаётся на том же месте экрана после смены scale — вместо CSS
// transform-origin, который считает не относительно точки на экране, а
// относительно самого элемента, и из-за этого при масштабировании не от
// центра часть картинки "уезжает" в отрицательные координаты, недоступные
// прокруткой (см. также margin: auto в стилях — вторая часть той же
// проблемы с непрокручиваемым верхним/левым краем).
function zoomAt(nextScale, pointerX, pointerY) {
  const wrapper = wrapperEl.value
  const prevScale = scale.value
  if (nextScale === prevScale) return
  if (!wrapper) { scale.value = nextScale; return }

  const contentX = wrapper.scrollLeft + pointerX
  const contentY = wrapper.scrollTop + pointerY
  const ratio = nextScale / prevScale

  scale.value = nextScale
  nextTick(() => {
    wrapper.scrollLeft = contentX * ratio - pointerX
    wrapper.scrollTop = contentY * ratio - pointerY
  })
}

function zoomIn() {
  const wrapper = wrapperEl.value
  zoomAt(clampScale(scale.value + SCALE_STEP), (wrapper?.clientWidth ?? 0) / 2, (wrapper?.clientHeight ?? 0) / 2)
}
function zoomOut() {
  const wrapper = wrapperEl.value
  zoomAt(clampScale(scale.value - SCALE_STEP), (wrapper?.clientWidth ?? 0) / 2, (wrapper?.clientHeight ?? 0) / 2)
}

function goTo(i) {
  index.value = (i + props.images.length) % props.images.length
  // Сбрасываем и посчитанный размер — иначе до срабатывания @load у новой
  // картинки короткое время показывался бы habitat-размер предыдущей
  // (другого соотношения сторон). Пока идёт загрузка, работает CSS-фолбэк
  // (max-width/max-height/object-fit), как и при самом первом открытии.
  fitWidth.value = 0
  fitHeight.value = 0
  resetZoom()
  emit('change-index', index.value)
}
function prev() { goTo(index.value - 1) }
function next() { goTo(index.value + 1) }

function changeBrightness(delta) {
  brightness.value = Math.max(0, Math.min(200, brightness.value + delta))
  saveSettings()
  notify(`Яркость: ${brightness.value}%`)
}
function changeContrast(delta) {
  contrast.value = Math.max(0, Math.min(200, contrast.value + delta))
  saveSettings()
  notify(`Контраст: ${contrast.value}%`)
}
function resetFilters() {
  brightness.value = 100
  contrast.value = 100
  saveSettings()
  notify('Настройки сброшены')
}
function toggleZoom() {
  zoomEnabled.value = !zoomEnabled.value
  saveSettings()
  notify(zoomEnabled.value ? 'Зум колесиком включён' : 'Зум колесиком выключен')
}

function onWheel(e) {
  if (!zoomEnabled.value) return
  e.preventDefault()
  const rect = wrapperEl.value.getBoundingClientRect()
  const nextScale = clampScale(scale.value + (e.deltaY < 0 ? SCALE_STEP : -SCALE_STEP))
  zoomAt(nextScale, e.clientX - rect.left, e.clientY - rect.top)
}

function onMouseDown(e) {
  if (scale.value <= 1) return
  isDragging.value = true
  dragStartX = e.clientX
  dragStartY = e.clientY
  scrollStartLeft = wrapperEl.value.scrollLeft
  scrollStartTop = wrapperEl.value.scrollTop
  e.preventDefault()
}
function onMouseMove(e) {
  if (!isDragging.value) return
  wrapperEl.value.scrollLeft = scrollStartLeft + (dragStartX - e.clientX)
  wrapperEl.value.scrollTop = scrollStartTop + (dragStartY - e.clientY)
}
function stopDragging() { isDragging.value = false }

function onKeydown(e) {
  switch (e.key) {
    case 'Escape': emit('close'); break
    case 'ArrowLeft': prev(); break
    case 'ArrowRight': next(); break
    case '+': case '=': zoomIn(); break
    case '-': zoomOut(); break
    case '0': resetZoom(); break
    case 'z': case 'Z': toggleZoom(); break
    case 'r': case 'R': resetFilters(); break
  }
}

onMounted(async () => {
  loadSettings()
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('resize', computeFitSize)
  await nextTick()
  computeFitSize()
  resetZoom()
})

onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('resize', computeFitSize)
  clearTimeout(notificationTimer)
})
</script>

<template>
  <div class="lightbox-overlay" @click.self="emit('close')">
    <div class="lightbox-container">
      <div class="lightbox-controls">
        <div class="controls-group">
          <button type="button" class="ctrl-btn ctrl-btn--labeled" title="Уменьшить яркость (B)" @click="changeBrightness(-10)">
            <svg viewBox="0 0 20 20" class="icon"><circle cx="10" cy="10" r="3.5" /><path d="M10 2v2.2M10 15.8V18M2 10h2.2M15.8 10H18M4.8 4.8l1.6 1.6M13.6 13.6l1.6 1.6M4.8 15.2l1.6-1.6M13.6 6.4l1.6-1.6" /></svg>
            <span>−</span>
          </button>
          <button type="button" class="ctrl-btn ctrl-btn--labeled" title="Увеличить яркость (Shift+B)" @click="changeBrightness(10)">
            <svg viewBox="0 0 20 20" class="icon"><circle cx="10" cy="10" r="3.5" /><path d="M10 2v2.2M10 15.8V18M2 10h2.2M15.8 10H18M4.8 4.8l1.6 1.6M13.6 13.6l1.6 1.6M4.8 15.2l1.6-1.6M13.6 6.4l1.6-1.6" /></svg>
            <span>+</span>
          </button>
          <button type="button" class="ctrl-btn ctrl-btn--labeled" title="Уменьшить контраст (C)" @click="changeContrast(-10)">
            <svg viewBox="0 0 20 20" class="icon"><circle cx="10" cy="10" r="7" /><path d="M10 3a7 7 0 0 0 0 14z" fill="currentColor" stroke="none" /></svg>
            <span>−</span>
          </button>
          <button type="button" class="ctrl-btn ctrl-btn--labeled" title="Увеличить контраст (Shift+C)" @click="changeContrast(10)">
            <svg viewBox="0 0 20 20" class="icon"><circle cx="10" cy="10" r="7" /><path d="M10 3a7 7 0 0 0 0 14z" fill="currentColor" stroke="none" /></svg>
            <span>+</span>
          </button>
          <button type="button" class="ctrl-btn" title="Сбросить фильтры (R)" @click="resetFilters">
            <svg viewBox="0 0 20 20" class="icon"><path d="M16 10a6 6 0 1 1-2-4.5" /><path d="M16 3v3.5h-3.5" /></svg>
          </button>
        </div>
        <div class="controls-center">
          <span class="counter" v-if="images.length > 1">{{ index + 1 }} / {{ images.length }}</span>
          <span class="indicator">{{ Math.round(scale * 100) }}%</span>
          <span class="indicator indicator--muted">Ярк {{ brightness }}% · Контр {{ contrast }}%</span>
        </div>
        <div class="controls-group">
          <button
            type="button" class="ctrl-btn" :class="{ 'ctrl-btn--active': zoomEnabled }"
            :title="`Зум колесиком (Z) — ${zoomEnabled ? 'ВКЛ' : 'ВЫКЛ'}`" @click="toggleZoom"
          >
            <svg viewBox="0 0 20 20" class="icon"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /><path v-if="!zoomEnabled" d="M4 4l9 9" /></svg>
          </button>
          <button type="button" class="ctrl-btn" title="Уменьшить (-)" @click="zoomOut">
            <svg viewBox="0 0 20 20" class="icon"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /><path d="M6 8.5h5" /></svg>
          </button>
          <button type="button" class="ctrl-btn" title="Сбросить масштаб (0)" @click="resetZoom">
            <svg viewBox="0 0 20 20" class="icon"><path d="M3 7V4a1 1 0 0 1 1-1h3M17 7V4a1 1 0 0 0-1-1h-3M3 13v3a1 1 0 0 0 1 1h3M17 13v3a1 1 0 0 1-1 1h-3" /></svg>
          </button>
          <button type="button" class="ctrl-btn" title="Увеличить (+)" @click="zoomIn">
            <svg viewBox="0 0 20 20" class="icon"><circle cx="8.5" cy="8.5" r="5.5" /><path d="M16.5 16.5 12.8 12.8" /><path d="M6 8.5h5M8.5 6v5" /></svg>
          </button>
          <button type="button" class="ctrl-btn ctrl-btn--close" title="Закрыть (Esc)" @click="emit('close')">
            <svg viewBox="0 0 20 20" class="icon"><path d="M5 5l10 10M15 5 5 15" /></svg>
          </button>
        </div>
      </div>

      <div class="lightbox-image-area">
        <button v-if="images.length > 1" type="button" class="nav-btn nav-prev" aria-label="Предыдущее изображение" @click="prev">
          <svg viewBox="0 0 20 20" class="icon icon--nav"><polyline points="12.5 4 6.5 10 12.5 16" /></svg>
        </button>

        <div
          ref="wrapperEl" class="image-wrapper" :class="{ zoomed: scale > 1, dragging: isDragging }"
          @wheel="onWheel" @mousedown="onMouseDown" @mousemove="onMouseMove" @mouseup="stopDragging" @mouseleave="stopDragging"
        >
          <img
            ref="imgEl" :src="current" :alt="alt"
            :style="{
              width: displayWidth ? `${displayWidth}px` : undefined,
              height: displayHeight ? `${displayHeight}px` : undefined,
              maxWidth: displayWidth ? 'none' : undefined,
              maxHeight: displayHeight ? 'none' : undefined,
              filter: filterCss,
              cursor: scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in',
            }"
            @click="scale <= 1 && zoomIn()"
            @load="computeFitSize"
          />
        </div>

        <button v-if="images.length > 1" type="button" class="nav-btn nav-next" aria-label="Следующее изображение" @click="next">
          <svg viewBox="0 0 20 20" class="icon icon--nav"><polyline points="7.5 4 13.5 10 7.5 16" /></svg>
        </button>
      </div>

      <div v-if="images.length > 1" class="lightbox-thumbs">
        <button
          v-for="(img, i) in images" :key="img"
          type="button" class="lightbox-thumb" :class="{ 'lightbox-thumb--active': i === index }"
          @click="goTo(i)"
        >
          <img :src="img" :style="{ filter: filterCss }" :alt="`Миниатюра ${i + 1}`" />
        </button>
      </div>

      <transition name="fade">
        <div v-if="notification" class="lightbox-notification">{{ notification }}</div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.lightbox-overlay {
  position: fixed; inset: 0; z-index: var(--z-overlay, 1999);
  background: rgba(42,33,24,0.96); display: flex; align-items: center; justify-content: center;
  animation: lightbox-fade-in var(--anim-fast, 150ms) ease;
  font-family: var(--font-body);
}
@keyframes lightbox-fade-in { from { opacity: 0 } to { opacity: 1 } }
.lightbox-container {
  width: 100%; height: 100%; background: var(--color-ink);
  display: flex; flex-direction: column; position: relative; overflow: hidden;
}
.lightbox-controls {
  display: flex; align-items: center; justify-content: space-between; gap: var(--space-1);
  padding: 10px var(--space-2); background: rgba(0,0,0,0.2);
  border-bottom: 1.5px dashed rgba(232,223,200,0.25); flex-wrap: wrap;
}
.controls-group { display: flex; gap: 6px; }
.controls-center { display: flex; align-items: center; gap: var(--space-2); color: var(--color-oak); font-size: 13px; }
.counter { font-family: var(--font-display); font-weight: 700; color: var(--color-bg); letter-spacing: 0.02em; }
.indicator { color: var(--color-oak); }
.indicator--muted { color: rgba(232,223,200,0.55); }
.ctrl-btn {
  width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; gap: 2px;
  background: transparent; border: 1.5px solid rgba(232,223,200,0.25); border-radius: var(--radius);
  color: var(--color-oak); cursor: pointer; transition: background var(--anim-fast, 150ms), border-color var(--anim-fast, 150ms), color var(--anim-fast, 150ms);
}
.ctrl-btn--labeled { width: auto; padding: 0 8px; font-size: 12px; font-weight: 700; }
.ctrl-btn:hover { border-color: var(--color-ochre); color: var(--color-ochre); }
.ctrl-btn--active { background: var(--color-ochre); border-color: var(--color-ochre); color: var(--color-ink); }
.ctrl-btn--active:hover { color: var(--color-ink); }
.ctrl-btn--close:hover { border-color: var(--color-oak); color: var(--color-bg); background: rgba(232,223,200,0.12); }
.icon { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

.lightbox-image-area { flex: 1; position: relative; display: flex; align-items: center; justify-content: center; padding: 0; overflow: hidden; min-height: 0; }
.image-wrapper {
  width: 100%; height: 100%; overflow: hidden; display: flex;
  position: relative;
}
.image-wrapper.zoomed { overflow: auto; cursor: grab; }
.image-wrapper.dragging { cursor: grabbing; }
.image-wrapper img {
  /* margin: auto вместо align-items/justify-content: center на .image-wrapper —
     центрирует картинку, пока она меньше контейнера, но (в отличие от
     центрирования на самом флекс-контейнере) не обрезает прокрутку к
     верхнему/левому краю, когда картинка становится больше контейнера при
     зуме: без этого приёма overflow в сторону начала осей недоступен прокруткой
     ни в одном браузере — известная особенность flexbox-центрирования. */
  display: block; margin: auto; max-width: 100%; max-height: 100%; object-fit: contain;
  transition: filter 200ms ease; user-select: none;
  border-radius: var(--radius);
}

.nav-btn {
  position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
  width: 48px; height: 48px; border-radius: 50%; border: 1.5px solid rgba(232,223,200,0.25);
  background: rgba(0,0,0,0.3); color: var(--color-oak); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background var(--anim-fast, 150ms), border-color var(--anim-fast, 150ms), transform var(--anim-fast, 150ms);
}
.nav-btn:hover { background: var(--color-ochre); border-color: var(--color-ochre); color: var(--color-ink); transform: translateY(-50%) scale(1.06); }
.nav-prev { left: var(--space-2); }
.nav-next { right: var(--space-2); }
.icon--nav { width: 20px; height: 20px; }

.lightbox-thumbs { display: flex; gap: var(--space-1); padding: var(--space-2); background: rgba(0,0,0,0.2); border-top: 1.5px dashed rgba(232,223,200,0.25); overflow-x: auto; }
.lightbox-thumb {
  flex: 0 0 auto; width: 56px; height: 56px; border-radius: var(--radius); overflow: hidden;
  border: 1.5px solid transparent; padding: 0; cursor: pointer; opacity: 0.55; transition: all var(--anim-fast, 150ms);
}
.lightbox-thumb--active, .lightbox-thumb:hover { opacity: 1; border-color: var(--color-ochre); }
.lightbox-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

.lightbox-notification {
  position: absolute; top: 64px; left: 50%; transform: translateX(-50%);
  background: var(--color-ochre); color: var(--color-bg); padding: 8px 18px; border-radius: 999px;
  font-size: 13px; font-weight: 500; z-index: 3; box-shadow: var(--shadow-sm);
}
.fade-enter-active, .fade-leave-active { transition: opacity var(--anim-fast, 150ms); }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 767px) {
  .controls-center { order: 3; width: 100%; justify-content: center; }
  .indicator--muted { display: none; }
  .nav-btn { width: 40px; height: 40px; }
}
</style>
