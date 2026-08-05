<script setup>
// Карусель фото из текста объекта — визуально повторяет галерею попапа
// (AttractionPopup: рамка + счётчик + стрелки + миниатюры + клик → полный
// экран через ImageLightbox), но дополнительно показывает подпись к каждому
// фото — комментарий, который в исходном тексте обычно идёт строкой под
// картинкой (см. useAttractions.js: extractGalleryAndCleanContent).
import { ref, computed } from 'vue'
import ImageLightbox from '@/components/ImageLightbox.vue'

const props = defineProps({
  items: { type: Array, required: true }, // [{ src, caption }]
  alt: { type: String, default: '' },
})

const activeIndex = ref(0)
const lightboxOpen = ref(false)
const active = computed(() => props.items[activeIndex.value])

function goTo(i) {
  activeIndex.value = (i + props.items.length) % props.items.length
}
</script>

<template>
  <div v-if="items.length" class="gallery">
    <div class="gallery-frame">
      <span v-if="items.length > 1" class="gallery-counter">{{ activeIndex + 1 }} / {{ items.length }}</span>
      <button v-if="items.length > 1" type="button" class="gallery-nav gallery-nav--prev" @click="goTo(activeIndex - 1)" aria-label="Предыдущее фото">
        <svg viewBox="0 0 20 20" class="icon"><polyline points="12.5 4 6.5 10 12.5 16" /></svg>
      </button>
      <transition name="gallery-fade" mode="out-in">
        <img :key="activeIndex" :src="active.src" :alt="active.caption || alt" class="gallery-img" @click="lightboxOpen = true" />
      </transition>
      <span class="gallery-zoom-hint" aria-hidden="true">⤢ Во весь экран</span>
      <button v-if="items.length > 1" type="button" class="gallery-nav gallery-nav--next" @click="goTo(activeIndex + 1)" aria-label="Следующее фото">
        <svg viewBox="0 0 20 20" class="icon"><polyline points="7.5 4 13.5 10 7.5 16" /></svg>
      </button>
    </div>

    <transition name="gallery-fade" mode="out-in">
      <p v-if="active.caption" :key="activeIndex" class="gallery-caption">{{ active.caption }}</p>
    </transition>

    <div v-if="items.length > 1" class="gallery-thumbs">
      <button
        v-for="(item, i) in items" :key="item.src" type="button"
        class="gallery-thumb" :class="{ 'gallery-thumb--active': i === activeIndex }"
        @click="goTo(i)" :aria-label="`Фото ${i + 1}`" :aria-current="i === activeIndex"
      >
        <img :src="item.src" :alt="`Фото ${i + 1}`" />
      </button>
    </div>

    <Teleport to="body">
      <ImageLightbox
        v-if="lightboxOpen"
        :images="items.map(i => i.src)" :initial-index="activeIndex" :alt="active.caption || alt"
        @close="lightboxOpen = false" @change-index="i => activeIndex = i"
      />
    </Teleport>
  </div>
</template>

<style scoped>
.gallery-frame {
  position: relative; border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-birch);
}
.gallery-img { width: 100%; max-height: 480px; object-fit: contain; display: block; background: var(--color-birch); cursor: zoom-in; }
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

.gallery-caption {
  font-family: var(--font-body); font-style: italic; font-size: 14px; line-height: 1.6; color: var(--color-muted);
  text-align: center; margin: var(--space-2) auto 0; max-width: 65ch;
}

.gallery-thumbs { display: flex; flex-wrap: wrap; gap: 8px; margin-top: var(--space-2); }
.gallery-thumb {
  flex: 0 0 auto; width: 60px; height: 60px; border-radius: var(--radius); overflow: hidden;
  border: 2px solid transparent; padding: 0; cursor: pointer; opacity: 0.55; transition: all 150ms;
}
.gallery-thumb--active, .gallery-thumb:hover { opacity: 1; border-color: var(--color-ochre); }
.gallery-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
