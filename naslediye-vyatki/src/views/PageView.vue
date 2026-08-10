<script setup>
import { computed, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { usePage } from '@/composables/usePage'
import CommentsSection from '@/components/CommentsSection.vue'
import defaultHeroImage from '@/assets/img/kremlin.jpeg'

// Общий шаблон для любой WP-страницы (Страницы, включая "О проекте") —
// slug приходит либо параметром маршрута (/page/:slug), либо статично
// зафиксирован в самом маршруте (/about, см. src/router/index.js).
const props = defineProps({ slug: { type: String, required: true } })
const { page, loading, error, fetchPage } = usePage()

// Фон hero — фото, привязанное к странице (featured image), если оно есть;
// иначе дефолтная картинка шаблона — та же, что раньше была захардкожена
// в AboutView.vue, теперь общий фолбэк для любой WP-страницы без своего фото.
const heroImage = computed(() => page.value?.imgSrc || defaultHeroImage)

function load(slug) {
  fetchPage(slug)
}

onMounted(() => load(props.slug))
watch(() => props.slug, (slug) => load(slug))
</script>

<template>
  <div class="page">
    <section v-if="page" class="hero">
      <div class="hero-bg" :style="{ backgroundImage: `url(${heroImage})` }" />
      <div class="hero-overlay" />
      <div class="hero-panel">
        <h1 class="hero-title">{{ page.title }}</h1>
      </div>
    </section>
    <div v-else class="spacer" />

    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <span class="current">{{ page?.title || '…' }}</span>
    </nav>

    <p v-if="loading" class="state-msg">Загрузка…</p>
    <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить страницу. Попробуйте обновить.</p>
    <template v-else-if="!page">
      <p class="state-msg">Страница не найдена.</p>
      <div class="back-bar">
        <RouterLink to="/" class="cta-btn">На главную</RouterLink>
      </div>
    </template>

    <template v-else>
      <section v-if="page.content" class="section">
        <div class="content" v-html="page.content" />
      </section>

      <CommentsSection :post-id="page.id" :comment-status="page.commentStatus" />
    </template>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.state-msg { text-align: center; color: var(--color-muted); padding: var(--space-5) var(--space-3); }
.state-msg--error { color: var(--color-error, #b3261e); }

.hero {
  position: relative; height: 44vh; min-height: 340px; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  margin-top: -80px; padding-top: 80px;
}
.hero-bg {
  position: absolute; inset: 0; z-index: 0; background-size: cover; background-position: center;
  filter: sepia(50%) brightness(0.8);
}
.hero-overlay {
  position: absolute; inset: 0; z-index: 1;
  background: linear-gradient(to bottom, rgba(60,122,140,0.35), rgba(42,33,24,0.25));
}
.hero-panel { position: relative; z-index: 2; text-align: center; padding: 0 var(--space-3); }
.hero-title {
  font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,44px);
  color: var(--color-bg); margin: 0; text-shadow: 0 2px 12px rgba(42,33,24,0.45);
}

.breadcrumb { max-width: 1000px; margin: 0 auto; padding: var(--space-2) var(--space-3) 0; display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; font-size: 13px; color: var(--color-muted) }
.breadcrumb a { color: var(--color-ochre); text-decoration: none }
.current { color: var(--color-ink) }

.section { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5) }
.content { line-height: 1.75; color: var(--color-ink) }
.content :deep(img) { max-width: 100%; border-radius: var(--radius); }
.content :deep(center) { display: block; text-align: center; margin: 1em 0; }

/* Заголовки/списки в свободном WP-контенте — единый стиль вместо стандартных
   стилей браузера, которые иначе клэшатся с дизайн-системой проекта. */
.content :deep(h2) {
  font-family: var(--font-display); font-weight: 700; font-size: clamp(22px,2.5vw,28px);
  margin: var(--space-4) 0 var(--space-2); color: var(--color-ink);
}
.content :deep(h2:first-child) { margin-top: 0; }
.content :deep(h3) { font-family: var(--font-display); font-weight: 700; font-size: 18px; margin: var(--space-3) 0 var(--space-1); color: var(--color-ink); }
.content :deep(ul) { margin: 0 0 1em var(--space-3); padding: 0; line-height: 1.8; }
.content :deep(li) { margin-bottom: 0.3em; }

/* Карточки статистики — общий класс "stat-grid"/"stat-card" для любой
   WP-страницы, не только "О проекте". */
.content :deep(.stat-grid) {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: var(--space-2);
  margin: var(--space-2) 0 var(--space-3);
}
.content :deep(.stat-card) {
  display: flex; flex-direction: column; align-items: center; text-align: center; gap: 4px;
  padding: var(--space-3); border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  background: var(--color-surface);
}
.content :deep(.stat-n) { font-family: var(--font-display); font-size: 32px; color: var(--color-teal); }
.content :deep(.stat-label) { font-size: 13px; color: var(--color-muted); }

/* Сетка карточек-ссылок на разделы — общий класс "module-grid"/"module-card". */
.content :deep(.module-grid) {
  display: grid; grid-template-columns: repeat(auto-fill,minmax(240px,1fr)); gap: var(--space-2);
  margin: var(--space-2) 0 var(--space-3);
}
.content :deep(.module-card) {
  display: flex; flex-direction: column; gap: 4px; padding: var(--space-2);
  border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  background: var(--color-surface); text-decoration: none; color: inherit;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms;
}
.content :deep(.module-card:hover) { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.content :deep(.module-title) { font-family: var(--font-display); font-weight: 700; font-size: 16px; color: var(--color-ink); }
.content :deep(.module-desc) { font-size: 13px; color: var(--color-muted); line-height: 1.5; }

/* Блок призыва к действию — общий класс "cta-block", кнопка переиспользует
   тот же .cta-btn, что и остальные CTA-кнопки сайта. */
.content :deep(.cta-block) { text-align: center; padding: var(--space-3) 0 0; }
.content :deep(.cta-block p) { color: var(--color-teal); font-size: 16px; margin: 0 0 var(--space-2); }

.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; display: flex; justify-content: center; }

.cta-btn, .content :deep(.cta-btn) {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface);
  text-decoration: none; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover, .content :deep(.cta-btn:hover) { background: var(--color-teal); transform: translateY(-1px) }
</style>
