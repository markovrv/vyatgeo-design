<script setup>
import { computed, onMounted, ref } from 'vue'
import { useComments, useCurrentUser, loadGuestAuthor, saveGuestAuthor } from '@/composables/useComments'
import { useStrings } from '@/i18n'
const { t } = useStrings()

const props = defineProps({
  postId: { type: [Number, String], required: true },
  commentStatus: { type: String, default: 'closed' },
})

const { comments, loading, error, submitting, submitError, submitted, fetchComments, postComment } = useComments()
const { user: currentUser, fetchCurrentUser } = useCurrentUser()
const isAuthenticated = computed(() => !!currentUser.value)

const authorName = ref('')
const authorEmail = ref('')
const content = ref('')
const formError = ref('')

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function resetForm() {
  authorName.value = ''
  authorEmail.value = ''
  content.value = ''
  formError.value = ''
}

function validateForm() {
  if (!authorName.value.trim()) return t('components.CommentsSection.nameRequired')
  if (!authorEmail.value.trim()) return t('components.CommentsSection.emailRequired')
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(authorEmail.value.trim())) return t('components.CommentsSection.emailInvalid')
  if (!content.value.trim()) return t('components.CommentsSection.contentRequired')
  return null
}

async function onSubmit() {
  formError.value = ''
  const validationError = validateForm()
  if (validationError) { formError.value = validationError; return }

  const trimmedName = authorName.value.trim()
  const trimmedEmail = authorEmail.value.trim()

  await postComment(Number(props.postId), {
    authorName: trimmedName,
    authorEmail: trimmedEmail,
    content: content.value.trim(),
  })
  if (!submitError.value) {
    // Только для гостя — залогиненный и так получает имя/email через API
    // при каждом заходе, запоминать их локально незачем.
    if (!isAuthenticated.value) saveGuestAuthor(trimmedName, trimmedEmail)
    resetForm()
  }
}

onMounted(() => {
  fetchComments(Number(props.postId))
  fetchCurrentUser().then(() => {
    if (currentUser.value) {
      authorName.value = currentUser.value.name
      authorEmail.value = currentUser.value.email
      return
    }
    const guest = loadGuestAuthor()
    if (guest) {
      authorName.value = guest.name
      authorEmail.value = guest.email
    }
  })
})
</script>

<template>
  <section class="comments-section">
    <h2 class="comments-heading">{{ t('components.CommentsSection.heading', { count: comments.length }) }}</h2>

    <p v-if="loading" class="msg">{{ t('components.CommentsSection.loading') }}</p>
    <p v-else-if="error" class="msg msg--error">{{ t('components.CommentsSection.loadError') }}</p>

    <ul v-if="comments.length" class="comments-list">
      <li v-for="c in comments" :key="c.id" class="comment-item">
        <div class="comment-meta">
          <strong class="comment-author">{{ c.authorName }}</strong>
          <span class="comment-date">{{ formatDate(c.date) }}</span>
        </div>
        <div class="comment-body" v-html="c.content" />
      </li>
    </ul>

    <template v-if="commentStatus === 'open'">
      <div v-if="submitted" class="success-msg">
        {{ t('components.CommentsSection.success') }}
      </div>

      <form v-else class="comment-form" @submit.prevent="onSubmit">
        <h3 class="form-heading">{{ t('components.CommentsSection.formHeading') }}</h3>

        <div v-if="formError" class="form-error">{{ formError }}</div>
        <div v-if="submitError === 'closed'" class="form-error">{{ t('components.CommentsSection.errClosed') }}</div>
        <div v-else-if="submitError === 'forbidden'" class="form-error">{{ t('components.CommentsSection.errForbidden') }}</div>
        <div v-else-if="submitError === 'network'" class="form-error">{{ t('components.CommentsSection.errNetwork') }}</div>
        <div v-else-if="submitError === 'duplicate'" class="form-error">{{ t('components.CommentsSection.errDuplicate') }}</div>

        <div class="form-grid">
          <input v-model="authorName" class="field" type="text" :placeholder="t('components.CommentsSection.namePlaceholder')" :disabled="submitting" />
          <input v-model="authorEmail" class="field" type="email" :placeholder="t('components.CommentsSection.emailPlaceholder')" :disabled="submitting" />
        </div>
        <textarea v-model="content" class="field field--textarea" rows="4" :placeholder="t('components.CommentsSection.contentPlaceholder')" :disabled="submitting" />

        <button class="submit-btn" type="submit" :disabled="submitting">
          {{ submitting ? t('components.CommentsSection.submitting') : t('components.CommentsSection.submit') }}
        </button>
      </form>
    </template>
  </section>
</template>

<style scoped>
.comments-section { max-width: 1000px; margin: 0 auto; padding: var(--space-3) var(--space-3) var(--space-5); }
.comments-heading { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 0 0 var(--space-2); text-align: center; }
.comments-count { font-size: 16px; color: var(--color-muted); font-family: var(--font-body); }

.msg { text-align: center; color: var(--color-muted); padding: var(--space-2) 0; }
.msg--error { color: var(--color-error, #b3261e); }

.comments-list { list-style: none; margin: 0; padding: 0; }
.comment-item { padding: var(--space-2) 0; border-bottom: 1px solid var(--color-border); }
.comment-item:first-child { border-top: 1px solid var(--color-border); }
.comment-meta { display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: baseline; margin-bottom: 6px; }
.comment-author { font-size: 14px; color: var(--color-ink); }
.comment-date { font-size: 12px; color: var(--color-muted); }
.comment-body { font-size: 14px; line-height: 1.6; color: var(--color-ink); }
.comment-body :deep(p) { margin: 0 0 0.5em; }
.comment-body :deep(p:last-child) { margin-bottom: 0; }

.success-msg { text-align: center; color: var(--color-teal); padding: var(--space-2) 0; font-size: 14px; }

.comment-form { margin-top: var(--space-3); padding: var(--space-2); border: 1.5px dashed var(--color-border); border-radius: var(--radius); }
.form-heading { font-size: 16px; margin: 0 0 var(--space-2); }

.form-error { color: var(--color-error, #b3261e); font-size: 13px; margin-bottom: var(--space-1); }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-2); margin-bottom: var(--space-2); }
@media (max-width: 767px) {
  .form-grid { grid-template-columns: 1fr; }
}

.field {
  width: 100%; min-height: 44px; padding: 10px 14px;
  border: 1.5px solid var(--color-border); border-radius: var(--radius);
  background: var(--color-surface); color: var(--color-ink);
  font-family: var(--font-body); font-size: 14px;
  transition: border-color 200ms; box-sizing: border-box;
}
.field:focus { outline: none; border-color: var(--color-ochre); }
.field--textarea { resize: vertical; margin-bottom: var(--space-2); }

.submit-btn {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  padding: 10px 24px; background: var(--color-ochre); color: var(--color-surface);
  border: none; cursor: pointer; min-height: 44px; letter-spacing: 0.03em;
  clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px);
  transition: background 300ms, transform 150ms;
}
.submit-btn:hover { background: var(--color-teal); transform: translateY(-1px) }
.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
