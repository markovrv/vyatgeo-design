<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useFinding, useFindingTypes, useFindingNav, useFindingSimilar, ethnographyCatalogState as catalogState, SITE_URL } from '@/composables/useFindings'
import ImageLightbox from '@/components/ImageLightbox.vue'

const route = useRoute()
const { finding, loading, error, fetchFinding } = useFinding()
const { types, fetchFindingTypes } = useFindingTypes()
const { prev, next, fetchAdjacent } = useFindingNav()
const { similar, fetchSimilar } = useFindingSimilar()

// Прямая ссылка в админку WP — для редакторов контента, не для посетителей,
// поэтому оформлена неприметной ссылкой, а не кнопкой.
const editUrl = computed(() => finding.value ? `${SITE_URL}wp-admin/post.php?post=${finding.value.id}&action=edit` : '')

const activeImage = ref(0)
const lightboxOpen = ref(false)

// Лупа при наведении на превью — как в оригинальном finding-gallery.js:
// увеличиваем только если изображение реально крупнее контейнера, точка
// увеличения следует за курсором.
function onFrameMouseEnter(e) {
  const img = e.target
  if (img.naturalWidth <= img.clientWidth && img.naturalHeight <= img.clientHeight) return
  img.style.transform = 'scale(1.5)'
}
function onFrameMouseMove(e) {
  const img = e.target
  if (img.style.transform !== 'scale(1.5)') return
  const rect = img.parentElement.getBoundingClientRect()
  const xPercent = ((e.clientX - rect.left) / rect.width) * 100
  const yPercent = ((e.clientY - rect.top) / rect.height) * 100
  img.style.transformOrigin = `${xPercent}% ${yPercent}%`
}
function onFrameMouseLeave(e) {
  e.target.style.transform = 'scale(1)'
  e.target.style.transformOrigin = 'center center'
}

// Навигация Пред/След — в рамках категории, если в каталоге выбран тип
// (ethnographyCatalogState.activeType); если выбрано "Все", идёт по всей коллекции.
function loadNav(id) {
  const typeSlug = catalogState.activeType === 'all' ? '' : catalogState.activeType
  fetchAdjacent(id, typeSlug)
}

onMounted(() => {
  fetchFinding(route.params.id)
  fetchFindingTypes()
  loadNav(route.params.id)
  fetchSimilar(route.params.id)
})

// Переход между объектами (например по ссылке) без размонтирования компонента —
// подгружаем заново, если меняется :id в уже открытой странице.
watch(() => route.params.id, (id) => {
  activeImage.value = 0
  fetchFinding(id)
  loadNav(id)
  fetchSimilar(id)
})

const typeIcon = computed(() => {
  const name = finding.value?.types[0]
  if (!name) return ''
  return types.value.find(t => t.name === name)?.image || ''
})

// Поля синхронизированы с get_finding_fields_html() из плагина artifact-finder —
// тот же состав и порядок, что и на детальной странице находки в wp-admin/на сервере.
const fields = computed(() => {
  if (!finding.value) return []
  const f = finding.value
  return [
    { label: 'Функционал', value: f.functionality },
    { label: 'Особенности', value: f.features },
    { label: 'Материал', value: f.materials.join(', ') },
    { label: 'Размеры (ш×д×в) см', value: f.dimensions },
    { label: 'Происхождение', value: f.origin.join(', ') },
    { label: 'Время поступления', value: f.receiptTime.join(', ') },
    { label: 'Время создания', value: f.creationTime.join(', ') },
  ].filter(field => field.value && field.value.trim())
})
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/ethnography">Этнографическое наследие</RouterLink><span>/</span>
      <span class="current">{{ finding?.title || '…' }}</span>
    </nav>

    <p v-if="loading" class="state-msg">Загрузка…</p>
    <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить данные. Попробуйте обновить страницу.</p>
    <p v-else-if="!finding" class="state-msg">Экспонат не найден.</p>

    <template v-else>
      <section class="intro">
        <div class="item-grid">
          <div class="gallery">
            <div class="frame">
              <img
                :src="finding.images[activeImage]" :alt="finding.title" class="frame-img"
                @mouseenter="onFrameMouseEnter" @mousemove="onFrameMouseMove" @mouseleave="onFrameMouseLeave"
                @click="lightboxOpen = true"
              />
              <span class="zoom-hint" aria-hidden="true">⤢ Нажмите для детального просмотра</span>
            </div>
            <div v-if="finding.images.length > 1" class="thumbs">
              <button
                v-for="(img, i) in finding.images" :key="img"
                type="button" class="thumb" :class="{ 'thumb--active': i === activeImage }"
                @click="activeImage = i"
              >
                <img :src="img" :alt="`${finding.title} — изображение ${i + 1}`" />
              </button>
            </div>
          </div>
          <div class="item-content">
            <div class="tags">
              <span v-if="finding.catId" class="tag tag--muted">{{ finding.catId }}</span>
              <span v-if="finding.types[0]" class="tag tag--ochre">
                <img v-if="typeIcon" :src="typeIcon" class="tag-icon" alt="" />
                {{ finding.types[0] }}
              </span>
            </div>
            <h1 class="title">{{ finding.title }}</h1>
            <p v-if="finding.content" class="desc">{{ finding.content }}</p>
          </div>
        </div>
      </section>

      <section v-if="fields.length" class="section">
        <div class="fields">
          <div v-for="field in fields" :key="field.label" class="field-row">
            <div class="field-name">{{ field.label }}</div>
            <div class="field-value">{{ field.value }}</div>
          </div>
        </div>
      </section>

      <nav v-if="prev || next" class="item-nav" aria-label="Навигация по экспонатам категории">
        <RouterLink v-if="prev" :to="`/ethnography/${prev.id}`" class="nav-card nav-card--prev">
          <div class="nav-thumb" :style="{ backgroundImage: `url(${prev.thumbnail})` }" />
          <div class="nav-text">
            <span class="nav-direction">
              <span class="nav-direction-full">← Предыдущий</span>
              <span class="nav-direction-short">← Пред.</span>
            </span>
            <span class="nav-title">{{ prev.title }}</span>
          </div>
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />

        <RouterLink v-if="next" :to="`/ethnography/${next.id}`" class="nav-card nav-card--next">
          <div class="nav-text">
            <span class="nav-direction">
              <span class="nav-direction-full">Следующий →</span>
              <span class="nav-direction-short">След. →</span>
            </span>
            <span class="nav-title">{{ next.title }}</span>
          </div>
          <div class="nav-thumb" :style="{ backgroundImage: `url(${next.thumbnail})` }" />
        </RouterLink>
        <span v-else class="nav-card nav-card--empty" />
      </nav>

      <div class="back-bar">
        <RouterLink to="/ethnography" class="cta-btn">← К каталогу экспонатов</RouterLink>
        <a v-if="editUrl" :href="editUrl" target="_blank" rel="noopener" class="edit-link">Редактировать</a>
      </div>

      <section v-if="similar.length" class="section similar-section">
        <h2 class="similar-heading">Похожие экспонаты</h2>
        <div class="similar-grid">
          <RouterLink v-for="s in similar" :key="s.id" :to="`/ethnography/${s.id}`" class="similar-card">
            <div class="similar-img" :style="{ backgroundImage: `url(${s.thumbnail})` }" />
            <div class="similar-body">
              <span v-if="s.types[0]" class="tag tag--ochre similar-tag">{{ s.types[0] }}</span>
              <h3 class="similar-title">{{ s.title }}</h3>
            </div>
          </RouterLink>
        </div>
      </section>

      <Teleport to="body">
        <ImageLightbox
          v-if="lightboxOpen"
          :images="finding.images" :initial-index="activeImage" :alt="finding.title"
          @close="lightboxOpen = false" @change-index="i => activeImage = i"
        />
      </Teleport>
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

.intro { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5) }
.item-grid { display: grid; grid-template-columns: 280px 1fr; gap: var(--space-4); align-items: start }
.frame { position: relative; border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden; background: var(--color-surface) }
.frame-img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; cursor: zoom-in; transition: transform 300ms ease; }
.zoom-hint {
  position: absolute; left: 0; right: 0; bottom: 0; padding: 6px 10px; text-align: center;
  font-size: 11px; color: var(--color-bg); background: rgba(42,33,24,0.65);
  opacity: 0; transition: opacity 200ms; pointer-events: none;
}
.frame:hover .zoom-hint { opacity: 1; }
.thumbs { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px }
.thumb { padding: 0; border: 2px solid transparent; border-radius: 4px; overflow: hidden; cursor: pointer; opacity: 0.7; background: none; }
.thumb img { width: 56px; height: 56px; object-fit: cover; display: block }
.thumb--active, .thumb:hover { opacity: 1; border-color: var(--color-teal) }
.tags { display: flex; gap: var(--space-1); flex-wrap: wrap; margin-bottom: 12px }
.tag { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; padding: 2px 10px; border-radius: 999px; color: var(--color-bg) }
.tag--teal { background: var(--color-teal) }
.tag--ochre { background: var(--color-ochre) }
.tag--muted { background: var(--color-muted) }
.tag-icon { width: 14px; height: 14px; object-fit: cover; border-radius: 50%; }
.title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 4px }
.desc { line-height: 1.75 }

.section { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3) var(--space-5) }
.fields { border-top: 1.5px dashed var(--color-border); padding-top: var(--space-3) }
.field-row { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--color-border); }
.field-name { font-weight: bold; font-size: 0.9em; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-muted); margin-bottom: 5px; }
.field-value { color: var(--color-ink); line-height: 1.4; font-size: 0.95em; }

.item-nav { max-width: 1000px; margin: 0 auto; padding: 0 var(--space-3); display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-2); }
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

.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }

.similar-section { padding-top: 0; }
.similar-heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2); text-align: center; }
.similar-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-2); }
.similar-card {
  display: block; text-decoration: none; color: inherit; background: var(--color-surface);
  border: 1.5px dashed var(--color-border); border-radius: var(--radius); overflow: hidden;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms;
}
.similar-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.similar-img { width: 100%; aspect-ratio: 4/3; background-size: cover; background-position: center; background-color: var(--color-birch); }
.similar-body { padding: var(--space-2); }
.similar-tag { display: inline-block; margin-bottom: 6px; }
.similar-title { font-family: var(--font-display); font-weight: 700; font-size: 14px; margin: 0; color: var(--color-ink); line-height: 1.3; }

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

@media (max-width: 1023px) {
  .item-grid { grid-template-columns: 240px 1fr; gap: var(--space-3) }
}
@media (max-width: 767px) {
  .item-grid { grid-template-columns: 1fr }
  .item-nav { grid-template-columns: 1fr }
  .nav-card--next { justify-content: flex-start; text-align: left; flex-direction: row-reverse; }
  .nav-card--empty { display: none; }
  .nav-direction-full { display: none; }
  .nav-direction-short { display: inline; }
}
</style>
