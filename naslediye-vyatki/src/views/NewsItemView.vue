<script setup>
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { usePost } from '@/composables/usePosts'
import { SITE_URL } from '@/composables/usePosts'
import CommentsSection from '@/components/CommentsSection.vue'

const route = useRoute()
const { post, loading, error, fetchPost } = usePost()

const editUrl = computed(() => post.value ? `${SITE_URL}wp-admin/post.php?post=${post.value.id}&action=edit` : '')

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}

function load(id) {
  fetchPost(id)
}

onMounted(() => load(route.params.id))
watch(() => route.params.id, (id) => load(id))
</script>

<template>
  <div class="page">
    <div class="spacer" />
    <nav class="breadcrumb">
      <RouterLink to="/">Главная</RouterLink><span>/</span>
      <RouterLink to="/news">Новости</RouterLink><span>/</span>
      <span class="current">{{ post?.title || '…' }}</span>
    </nav>

    <p v-if="loading" class="state-msg">Загрузка…</p>
    <p v-else-if="error" class="state-msg state-msg--error">Не удалось загрузить новость. Попробуйте обновить страницу.</p>
    <p v-else-if="!post" class="state-msg">Новость не найдена.</p>

    <template v-else>
      <section class="intro">
        <h1 class="title">{{ post.title }}</h1>
        <p v-if="post.date" class="meta">{{ formatDate(post.date) }}</p>
      </section>

      <section v-if="post.content" class="section">
        <div class="content" v-html="post.content" />
      </section>

      <div class="back-bar">
        <RouterLink to="/news" class="cta-btn">← К ленте новостей</RouterLink>
        <a v-if="editUrl" :href="editUrl" target="_blank" rel="noopener" class="edit-link">Редактировать</a>
      </div>

      <CommentsSection :post-id="post.id" :comment-status="post.commentStatus" />
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
.content { line-height: 1.75; color: var(--color-ink) }
.content :deep(img) { max-width: 100%; border-radius: var(--radius); }
.content :deep(center) { display: block; text-align: center; margin: 1em 0; }

.back-bar { padding: var(--space-3); max-width: 1000px; margin: 0 auto; border-top: 1.5px dashed var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-2) }

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
