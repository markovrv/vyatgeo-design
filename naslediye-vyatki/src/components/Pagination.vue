<script setup>
const props = defineProps({
  page: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  total: { type: Number, required: true },
  perPage: { type: Number, required: true },
  perPageOptions: { type: Array, default: () => [10, 20, 50, 100, 500] },
})

const emit = defineEmits(['update:page', 'update:perPage'])

function go(p) {
  const clamped = Math.min(Math.max(p, 1), Math.max(props.totalPages, 1))
  if (clamped !== props.page) emit('update:page', clamped)
}

function onPerPageChange(e) {
  emit('update:perPage', Number(e.target.value))
}
</script>

<template>
  <div class="pagination">
    <div class="pagination-info">
      <template v-if="total > 0">
        Показано {{ (page - 1) * perPage + 1 }}–{{ Math.min(page * perPage, total) }} из {{ total }}
      </template>
      <template v-else>Ничего не найдено</template>
    </div>

    <div class="pagination-controls">
      <button type="button" class="page-btn" :disabled="page <= 1" @click="go(1)" aria-label="Первая страница">«</button>
      <button type="button" class="page-btn" :disabled="page <= 1" @click="go(page - 1)" aria-label="Предыдущая страница">‹</button>
      <span class="page-current">Стр. {{ page }} из {{ Math.max(totalPages, 1) }}</span>
      <button type="button" class="page-btn" :disabled="page >= totalPages" @click="go(page + 1)" aria-label="Следующая страница">›</button>
      <button type="button" class="page-btn" :disabled="page >= totalPages" @click="go(totalPages)" aria-label="Последняя страница">»</button>
    </div>

    <label class="per-page">
      <span>Показывать по:</span>
      <select :value="perPage" @change="onPerPageChange">
        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
      </select>
    </label>
  </div>
</template>

<style scoped>
.pagination {
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
  gap: var(--space-2); margin-top: var(--space-3); padding-top: var(--space-3);
  border-top: 1.5px dashed var(--color-border); font-size: 13px; color: var(--color-muted);
}
.pagination-controls { display: flex; align-items: center; gap: 6px; }
.page-btn {
  min-width: 36px; min-height: 36px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center;
  font-family: var(--font-body); font-size: 14px; color: var(--color-ink);
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  cursor: pointer; transition: background 150ms, border-color 150ms;
}
.page-btn:hover:not(:disabled) { border-color: var(--color-ochre); color: var(--color-ochre); }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-current { padding: 0 6px; white-space: nowrap; }
.per-page { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
.per-page select {
  font-family: var(--font-body); font-size: 13px; color: var(--color-ink);
  background: var(--color-surface); border: 1.5px solid var(--color-border); border-radius: var(--radius);
  padding: 6px 10px; min-height: 36px; cursor: pointer;
}
.per-page select:hover { border-color: var(--color-ochre); }

@media (max-width: 767px) {
  .pagination { justify-content: center; text-align: center; }
}
</style>
