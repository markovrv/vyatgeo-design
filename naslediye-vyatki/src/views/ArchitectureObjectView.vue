<script setup>
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAttraction, useAttractionAdjacent, useAttractionNearby, useAttractionThumbnails, SITE_URL } from '@/composables/useAttractions'
import { ATTRACTION_NEARBY_RADIUS_KM } from '@/config/attraction'
import AttractionGallery from '@/components/AttractionGallery.vue'

const route = useRoute()
const { attraction, loading, error, fetchAttraction } = useAttraction()
const { prev, next, fetchAdjacent } = useAttractionAdjacent()
const { nearby, fetchNearby } = useAttractionNearby()
const { thumbnails, fetchThumbnails } = useAttractionThumbnails()

// Прямая ссылка в админку WP — для редакторов контента, не для посетителей,
// поэтому оформлена неприметной ссылкой, а не кнопкой.
const editUrl = computed(() => attraction.value ? `${SITE_URL}wp-admin/post.php?post=${attraction.value.id}&action=edit` : '')

function formatDistance(km) {
  if (km == null) return ''
  return km < 1 ? `${Math.round(km * 1000)} м` : `${km.toFixed(1)} км`
}

// Карточки "Объектов рядом" крупнее миниатюры из ответа nearby (та же WP
// миниатюра 150×150, что и у карточек каталога) — подтягиваем более крупный
// вариант тем же способом, что и в каталоге (см. useAttractionThumbnails).
function nearbyImg(n) {
  return thumbnails.value[n.imgId] || n.imgSrc
}
watch(nearby, (items) => fetchThumbnails(items.map(n => n.imgId)))

function load(id) {
  fetchAttraction(id)
  fetchAdjacent(id)
  fetchNearby(id, ATTRACTION_NEARBY_RADIUS_KM)
}

onMounted(() => load(route.params.id))
watch(() => route.params.id, (id) => load(id))
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/architecture">Архитектура Кирова</RouterLink><span>/</span>
      <span class="current">{{ attraction?.title || '…' }}</span>
    </nav>

    <p v-if="loading" class="state-msg">Загрузка…</p>
    <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
    <p v-else-if="!attraction" class="state-msg">Объект не найден.</p>

    <template v-else>
      <section class="intro">
        <h1 class="title">{{ attraction.title }}</h1>
        <p v-if="attraction.place" class="meta">{{ attraction.place }}</p>
      </section>

      <section v-if="attraction.summarize || attraction.content" class="section">
        <p v-if="attraction.summarize" class="summarize">{{ attraction.summarize }}</p>
        <div v-if="attraction.content" class="content" v-html="attraction.content" />
      </section>

      <section v-if="attraction.gallery?.length" class="section gallery-section">
        <h2 class="gallery-heading">Изображения</h2>
        <AttractionGallery :items="attraction.gallery" :alt="attraction.title" />
      </section>

      <nav v-if="prev || next" class="item-nav" aria-label="Навигация по объектам">
        <RouterLink v-if="prev" :to="`/architecture/${prev.id}`" class="nav-card nav-card--prev">
          <div class="nav-thumb" :style="{ backgroundImage: `url(${prev.imgSrc})` }" />
          <div class="nav-text">
            <span class="nav-direction">
              <span class="nav-direction-full">← Предыдущий</span>
              <span class="nav-direction-short">← Пред.</span>
            </span>
            <span class="nav-title" :title="prev.title">{{ prev.title }}</span>
          </div>
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />

        <RouterLink v-if="next" :to="`/architecture/${next.id}`" class="nav-card nav-card--next">
          <div class="nav-text">
            <span class="nav-direction">
              <span class="nav-direction-full">Следующий →</span>
              <span class="nav-direction-short">След. →</span>
            </span>
            <span class="nav-title" :title="next.title">{{ next.title }}</span>
          </div>
          <div class="nav-thumb" :style="{ backgroundImage: `url(${next.imgSrc})` }" />
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />
      </nav>

      <div class="back-bar">
        <RouterLink to="/architecture" class="cta-btn">← К карте и каталогу</RouterLink>
        <a v-if="editUrl" :href="editUrl" target="_blank" rel="noopener" class="edit-link">Редактировать</a>
      </div>

      <section v-if="nearby.length" class="section nearby-section">
        <h2 class="nearby-heading">Объекты рядом</h2>
        <div class="nearby-grid">
          <RouterLink v-for="n in nearby" :key="n.id" :to="`/architecture/${n.id}`" class="nearby-card">
            <div class="nearby-img" :style="{ backgroundImage: `url(${nearbyImg(n)})` }" />
            <div class="nearby-body">
              <span v-if="n.distanceKm != null" class="nearby-distance">{{ formatDistance(n.distanceKm) }}</span>
              <h3 class="nearby-title">{{ n.title }}</h3>
              <p v-if="n.place" class="nearby-place">{{ n.place }}</p>
            </div>
          </RouterLink>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.page { font-family: var(--font-body) }
.spacer { height: 80px }
.state-msg { text-align: center; color: var(--color-muted); padding: var(--space-5) var(--space-3); }
.state-msg--error { color: var(--color-error, #b3261e); }
.breadcrumb { max-width: 1000px; margin: 0 auto; padding: var(--space-2) var(--space-3) 0; display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; font-size: 13px; color: var(--color-muted) }
.breadcrumb a { color: var(--color-ochre); text-decoration: none }
.current { color: var(--color-ink) }

.intro { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) 0; text-align: center }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.meta { font-size: 14px; color: var(--color-teal); margin: 0 }

.section { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5) }
.summarize { font-family: var(--font-body); font-size: 17px; font-weight: 500; line-height: 1.7; color: var(--color-ink); margin: 0 0 var(--space-3) }
.content { line-height: 1.75; color: var(--color-ink) }
.content :deep(img) { max-width: 100%; border-radius: var(--radius); }
.content :deep(center) { display: block; text-align: center; margin: 1em 0; }

.gallery-heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2); text-align: center; }

/* minmax(0, 1fr), а не просто 1fr — иначе длинный несжимаемый (white-space:
   nowrap) заголовок раздувает свою колонку сетки шире 50%, а не обрезается. */
.item-nav { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3); display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: var(--space-2); }
.nav-card {
  display: flex; align-items: center; gap: var(--space-2); text-decoration: none; color: inherit;
  background: var(--color-surface); border: 1.5px dashed var(--color-border); border-radius: var(--radius);
  padding: 10px; transition: border-color 200ms, transform 200ms;
}
.nav-card:hover { border-color: var(--color-ochre); transform: translateY(-2px); }
.nav-card--next { justify-content: flex-end; text-align: right; }
.nav-card--empty { border-style: solid; border-color: transparent; background: none; }
.nav-thumb { flex-shrink: 0; width: 56px; height: 56px; border-radius: var(--radius); background-size: cover; background-position: center; background-color: var(--color-birch); }
.nav-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.nav-direction { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-teal); }
.nav-direction-short { display: none; }
.nav-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; color: var(--color-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

@media (max-width: 767px) {
  .nav-direction-full { display: none; }
  .nav-direction-short { display: inline; }
}

.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }

.nearby-section { padding-top: 0; }
.nearby-heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2); text-align: center; }
.nearby-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-2); }
.nearby-card {
  display: block; text-decoration: none; color: inherit; background: var(--color-surface);
  border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms;
}
.nearby-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.nearby-img { width: 100%; aspect-ratio: 4/3; background-size: cover; background-position: center; background-color: var(--color-birch); }
.nearby-body { padding: var(--space-2); }
.nearby-distance {
  display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; color: var(--color-bg);
  background: var(--color-teal); padding: 2px 10px; border-radius: 999px; margin-bottom: 6px;
}
.nearby-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; margin: 0; color: var(--color-ink); line-height: 1.3; }
.nearby-place { font-size: 12px; color: var(--color-muted); margin: 4px 0 0; }

.cta-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface);
  text-decoration: none; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.cta-btn:hover { background: var(--color-teal); transform: translateY(-1px) }

.edit-link {
  display: inline-flex; align-items: center; min-height: 44px; padding: 10px 6px;
  font-family: var(--font-body); font-size: 12px; color: var(--color-muted);
  text-decoration: none; opacity: 0.65; transition: opacity 150ms, color 150ms;
}
.edit-link:hover { opacity: 1; color: var(--color-teal); text-decoration: underline; text-underline-offset: 3px; }
</style>
