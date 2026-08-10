<script setup>
import { onMounted, nextTick } from 'vue'
import { RouterLink, onBeforeRouteLeave } from 'vue-router'
import { usePosts, postsCatalogState } from '@/composables/usePosts'
import Pagination from '@/components/Pagination.vue'

const { posts, total, totalPages, loading, error, fetchPosts } = usePosts()

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}

let searchTimer = null
function onSearchInput(e) {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    postsCatalogState.search = e.target.value
    postsCatalogState.page = 1
    doFetch()
  }, 400)
}

function doFetch() {
  fetchPosts({ page: postsCatalogState.page, perPage: postsCatalogState.perPage, search: postsCatalogState.search })
}

function goPage(p) {
  postsCatalogState.page = p
  doFetch()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Восстановление scrollY при возврате
onBeforeRouteLeave(() => { postsCatalogState.scrollY = window.scrollY })
onMounted(() => {
  doFetch()
  nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => {
    if (postsCatalogState.scrollY) window.scrollTo(0, postsCatalogState.scrollY)
  })))
})
</script>

<template>
  <div class="news-page">
    <div class="spacer" />
    <section class="hero">
      <h1 class="hero-title">Новости</h1>
      <p class="hero-desc">Актуальные события и новости проекта «Наследие Вятки»</p>
    </section>

    <section class="content-section">
      <div class="search-row">
        <input
          class="search-box" type="search" placeholder="Поиск по новостям…"
          :value="postsCatalogState.search" @input="onSearchInput"
        />
      </div>

      <p v-if="loading" class="state-msg">Загрузка…</p>
      <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить новости. Попробуйте обновить страницу.</p>
      <p v-else-if="!posts.length" class="state-msg">Новостей не найдено.</p>

      <template v-else>
        <div class="grid-count">Показано: {{ posts.length }} из {{ total }}</div>
        <div class="grid">
          <RouterLink v-for="p in posts" :key="p.id" :to="`/news/${p.id}`" class="news-card">
            <div v-if="p.imgSrc" class="news-img" :style="{ backgroundImage: `url(${p.imgSrc})` }" />
            <div class="news-body">
              <span class="news-date">{{ formatDate(p.date) }}</span>
              <h2 class="news-title">{{ p.title }}</h2>
              <p v-if="p.excerpt" class="news-excerpt" v-html="p.excerpt" />
            </div>
          </RouterLink>
        </div>

        <div v-if="totalPages > 1" class="pagination-row">
          <Pagination :page="postsCatalogState.page" :total-pages="totalPages" @go="goPage" />
        </div>
      </template>
    </section>
  </div>
</template>

<style scoped>
.news-page { font-family: var(--font-body) }
.spacer { height: 80px }
.state-msg { text-align: center; color: var(--color-muted); padding: var(--space-5) var(--space-3); }
.state-msg--error { color: var(--color-error, #b3261e); }

.hero { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) 0; text-align: center }
.hero-title { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px,3.5vw,42px); margin: 0 0 var(--space-1) }
.hero-desc { font-size: 16px; color: var(--color-muted); margin: 0 0 var(--space-3) }

.content-section { padding: 0 var(--space-3) var(--space-5); max-width: 1000px; margin: 0 auto }

.search-row { margin-bottom: var(--space-3); display: flex; justify-content: center }
.search-box {
  width: 100%; max-width: 480px; min-height: 44px; padding: 10px 16px;
  border: 1.5px solid var(--color-border); border-radius: var(--radius);
  background: var(--color-surface); color: var(--color-ink);
  font-family: var(--font-body); font-size: 15px;
  transition: border-color 200ms;
}
.search-box:focus { outline: none; border-color: var(--color-ochre); }

.grid-count { text-align: center; font-size: 13px; color: var(--color-muted); margin-bottom: var(--space-3) }
.grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: var(--space-3) }

.news-card {
  display: flex; flex-direction: column; text-decoration: none; color: inherit;
  background: var(--color-surface); border: 1.5px dashed var(--color-border);
  border-radius: var(--radius); overflow: hidden;
  transition: transform 300ms var(--ease-out), box-shadow 300ms var(--ease-out), border-color 150ms;
}
.news-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: var(--color-ochre); }
.news-img { width: 100%; aspect-ratio: 16/9; background-size: cover; background-position: center; background-color: var(--color-birch); }
.news-body { padding: var(--space-2); display: flex; flex-direction: column; gap: 4px; }
.news-date { font-size: 12px; color: var(--color-teal); }
.news-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; margin: 0; color: var(--color-ink); line-height: 1.3; }
.news-excerpt { font-size: 13px; color: var(--color-muted); line-height: 1.5; margin: 0; }
.news-excerpt :deep(p) { margin: 0; }

.pagination-row { margin-top: var(--space-3); display: flex; justify-content: center }
</style>
