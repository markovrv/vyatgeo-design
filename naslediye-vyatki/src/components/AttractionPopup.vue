<script setup>
// Всплывающее окно объекта при клике на точку карты — как в оригинальном
// прототипе (модалка вместо перехода на страницу), оформлено в стиле проекта.
// Полноэкранный просмотр фото делегирован ImageLightbox (тот же компонент,
// что и в галерее Этнографии) — без дублирования зума/клавиатуры/счётчика.
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { RouterLink } from 'vue-router'
import ImageLightbox from '@/components/ImageLightbox.vue'

const props = defineProps({
  attraction: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})
const emit = defineEmits(['close'])

const activeImage = ref(0)
const lightboxOpen = ref(false)
const closeBtnEl = ref(null)
const images = computed(() => props.attraction?.images || [])
const objectLink = computed(() => props.attraction ? `/architecture/${props.attraction.id}` : '#')

function goTo(i) {
  activeImage.value = (i + images.value.length) % images.value.length
}

// Текст в окне — строго аннотация объекта (attraction_summarize), без
// подстановки сырого текста статьи: если аннотации нет, просто не показываем блок.
const description = computed(() => props.attraction?.summarize || '')

function onKeydown(e) {
  if (lightboxOpen.value) return // у ImageLightbox свой обработчик Escape/стрелок
  if (e.key === 'Escape') emit('close')
  else if (e.key === 'ArrowLeft' && images.value.length > 1) goTo(activeImage.value - 1)
  else if (e.key === 'ArrowRight' && images.value.length > 1) goTo(activeImage.value + 1)
}

let previouslyFocused = null
onMounted(async () => {
  previouslyFocused = document.activeElement
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', onKeydown)
  await nextTick()
  closeBtnEl.value?.focus()
})
onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKeydown)
  previouslyFocused?.focus?.()
})
</script>

<template>
  <div
    class="popup-overlay" role="dialog" aria-modal="true" aria-labelledby="attraction-popup-title"
    @click.self="emit('close')"
  >
    <div class="popup-card">
      <button ref="closeBtnEl" type="button" class="popup-x" @click="emit('close')" aria-label="Закрыть">
        <svg viewBox="0 0 20 20" class="icon"><path d="M5 5l10 10M15 5 5 15" /></svg>
      </button>

      <p v-if="loading" class="popup-loading">Загрузка…</p>
      <template v-else-if="attraction">
        <div class="popup-body" :class="{ 'popup-body--split': images.length > 0 }">
          <header class="popup-header">
            <h3 id="attraction-popup-title" class="popup-title">
              <RouterLink :to="objectLink">{{ attraction.title }}</RouterLink>
            </h3>
            <p v-if="attraction.place" class="popup-place">
              <svg viewBox="0 0 20 20" class="icon icon--pin" aria-hidden="true"><path d="M10 18s6-5.6 6-10a6 6 0 1 0-12 0c0 4.4 6 10 6 10z" /><circle cx="10" cy="8" r="2.2" /></svg>
              {{ attraction.place }}
            </p>
          </header>

          <div v-if="images.length" class="popup-media">
            <div class="gallery-frame">
              <span v-if="images.length > 1" class="gallery-counter">{{ activeImage + 1 }} / {{ images.length }}</span>
              <button v-if="images.length > 1" type="button" class="gallery-nav gallery-nav--prev" @click="goTo(activeImage - 1)" aria-label="Предыдущее фото">
                <svg viewBox="0 0 20 20" class="icon"><polyline points="12.5 4 6.5 10 12.5 16" /></svg>
              </button>
              <transition name="gallery-fade" mode="out-in">
                <img
                  :key="activeImage" :src="images[activeImage]" :alt="attraction.title" class="gallery-img"
                  @click="lightboxOpen = true"
                />
              </transition>
              <span class="gallery-zoom-hint" aria-hidden="true">⤢ Во весь экран</span>
              <button v-if="images.length > 1" type="button" class="gallery-nav gallery-nav--next" @click="goTo(activeImage + 1)" aria-label="Следующее фото">
                <svg viewBox="0 0 20 20" class="icon"><polyline points="7.5 4 13.5 10 7.5 16" /></svg>
              </button>
            </div>
            <div v-if="images.length > 1" class="popup-thumbs">
              <button
                v-for="(img, i) in images" :key="img" type="button"
                class="popup-thumb" :class="{ 'popup-thumb--active': i === activeImage }"
                @click="goTo(i)" :aria-label="`Фото ${i + 1}`" :aria-current="i === activeImage"
              >
                <img :src="img" :alt="`Фото ${i + 1}`" />
              </button>
            </div>
          </div>

          <div class="popup-text">
            <p v-if="description" class="popup-desc">{{ description }}</p>

            <div class="popup-footer">
              <RouterLink :to="objectLink" class="popup-source-btn">
                Подробнее
                <svg viewBox="0 0 20 20" class="icon icon--sm"><polyline points="7.5 4 13.5 10 7.5 16" /></svg>
              </RouterLink>
              <button type="button" class="popup-close-link" @click="emit('close')">Закрыть</button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <Teleport to="body">
      <ImageLightbox
        v-if="lightboxOpen"
        :images="images" :initial-index="activeImage" :alt="attraction?.title || ''"
        @close="lightboxOpen = false" @change-index="i => activeImage = i"
      />
    </Teleport>
  </div>
</template>

<style scoped>
.popup-overlay {
  position: fixed; inset: 0; z-index: var(--z-overlay, 1999);
  background: rgba(42,33,24,0.55); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: var(--space-3);
  animation: popup-fade-in var(--anim-fast, 150ms) ease;
}
@keyframes popup-fade-in { from { opacity: 0 } to { opacity: 1 } }
.popup-card {
  position: relative; width: 100%; max-width: 880px; max-height: 90vh; overflow-y: auto;
  background: var(--color-surface); border-radius: var(--radius-lg, 8px); border: 1.5px dashed var(--color-border);
  box-shadow: var(--shadow-lg); padding: var(--space-4) var(--space-4) var(--space-3);
  animation: popup-card-in 220ms var(--ease-out, ease-out);
}
@keyframes popup-card-in {
  from { opacity: 0; transform: translateY(10px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.popup-x {
  position: absolute; top: var(--space-2); right: var(--space-2); z-index: 2;
  width: 36px; height: 36px; border-radius: 50%; border: 1.5px solid var(--color-border); background: var(--color-surface);
  color: var(--color-muted); display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: border-color 150ms, color 150ms;
}
.popup-x:hover { border-color: var(--color-ochre); color: var(--color-ochre); }
.popup-x .icon { width: 16px; height: 16px; }

.popup-loading { text-align: center; color: var(--color-muted); padding: var(--space-5) 0; margin: 0; }

/* Мобильный порядок (одна колонка): заголовок → галерея → текст. */
.popup-body { display: flex; flex-direction: column; padding-right: var(--space-4); }
.popup-header { order: 1; margin-bottom: var(--space-2); }
.popup-media { order: 2; }
.popup-text { order: 3; margin-top: var(--space-3); display: flex; flex-direction: column; }
.popup-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(20px,2.5vw,26px); margin: 0; line-height: 1.25; }
.popup-title a { color: var(--color-ink); text-decoration: none; transition: color 150ms; }
.popup-title a:hover { color: var(--color-ochre-dark, #8B5E2B); text-decoration: underline; text-underline-offset: 3px; }
.popup-place {
  display: inline-flex; align-items: center; gap: 5px; margin: 6px 0 0;
  font-size: 13px; color: var(--color-teal); font-weight: 500;
}
.icon--pin { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 1.8; flex-shrink: 0; }

.popup-desc {
  font-family: var(--font-body); font-size: 15px; line-height: 1.7; color: var(--color-ink);
  margin: 0; flex: 1;
}

.popup-media { min-width: 0; }
.gallery-frame {
  position: relative; border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-birch);
}
.gallery-img { width: 100%; max-height: 320px; object-fit: contain; display: block; background: var(--color-birch); cursor: zoom-in; }
.gallery-fade-enter-active, .gallery-fade-leave-active { transition: opacity 180ms ease; }
.gallery-fade-enter-from, .gallery-fade-leave-to { opacity: 0; }

.gallery-counter {
  position: absolute; top: 10px; right: 10px; z-index: 1;
  background: rgba(42,33,24,0.65); color: var(--color-oak); font-size: 12px; font-weight: 500;
  padding: 3px 10px; border-radius: 999px; letter-spacing: 0.02em; pointer-events: none;
}
.gallery-zoom-hint {
  position: absolute; left: 50%; bottom: 10px; transform: translateX(-50%) translateY(6px); z-index: 1;
  background: rgba(42,33,24,0.65); color: var(--color-oak); font-size: 12px;
  padding: 4px 12px; border-radius: 999px; opacity: 0; transition: opacity 150ms, transform 150ms; pointer-events: none;
}
.gallery-frame:hover .gallery-zoom-hint { opacity: 1; transform: translateX(-50%) translateY(0); }

.gallery-nav {
  position: absolute; top: 50%; transform: translateY(-50%); z-index: 1;
  width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(42,33,24,0.55);
  color: var(--color-bg); display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: background 150ms;
}
.gallery-nav:hover { background: var(--color-ochre); }
.gallery-nav--prev { left: 10px; }
.gallery-nav--next { right: 10px; }
.icon { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.icon--sm { width: 15px; height: 15px; }

.popup-thumbs { display: flex; flex-wrap: wrap; gap: 8px; margin-top: var(--space-2); }
.popup-thumb {
  flex: 0 0 auto; width: 52px; height: 52px; border-radius: var(--radius); overflow: hidden;
  border: 2px solid transparent; padding: 0; cursor: pointer; opacity: 0.55; transition: all 150ms;
}
.popup-thumb--active, .popup-thumb:hover { opacity: 1; border-color: var(--color-ochre); }
.popup-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

.popup-footer { display: flex; align-items: center; gap: var(--space-2); margin-top: var(--space-3); }
.popup-source-btn {
  display: inline-flex; align-items: center; gap: 8px;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 22px; background: var(--color-ochre); color: var(--color-surface);
  text-decoration: none; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.popup-source-btn:hover { background: var(--color-teal); transform: translateY(-1px); }
.popup-close-link {
  font-family: var(--font-body); font-size: 13px; font-weight: 700; color: var(--color-ochre-dark, #8B5E2B);
  background: none; border: none; cursor: pointer; padding: 10px 6px; min-height: 44px;
  text-decoration: underline; text-underline-offset: 3px;
}
.popup-close-link:hover { color: var(--color-teal); }

/* Две колонки, когда ширина позволяет (совпадает с основными брейкпоинтами
   проекта — 1023px/767px): медиа слева на всю высоту, заголовок и текст
   друг под другом справа. Без фото (popup-body--split не применяется)
   остаётся одна колонка на любой ширине. */
@media (min-width: 768px) {
  .popup-body--split {
    display: grid; grid-template-columns: minmax(320px, 1.15fr) minmax(240px, 1fr);
    grid-template-rows: auto 1fr; gap: 0 var(--space-4); align-items: start;
  }
  .popup-body--split .popup-header { grid-column: 2; grid-row: 1; margin-bottom: var(--space-2); }
  .popup-body--split .popup-media { grid-column: 1; grid-row: 1 / 3; }
  .popup-body--split .popup-text { grid-column: 2; grid-row: 2; margin-top: 0; height: 100%; }
  .popup-body--split .popup-footer { margin-top: auto; padding-top: var(--space-3); }
}

@media (max-width: 767px) {
  .popup-card { padding: var(--space-3); }
  .popup-body { padding-right: var(--space-3); }
  .gallery-img { max-height: 260px; }
}
</style>
