<script setup>
// Иконка кластера — круговая диаграмма из цветов объектов внутри кластера.
// Перенесено из старого прототипа (CircularChart.vue), адаптировано под
// Composition API и цвета дизайн-системы вместо жёстко заданных значений.
import { ref, onMounted } from 'vue'

const props = defineProps({
  colors: { type: Array, required: true },
  size: { type: Number, default: 56 },
})

const canvasEl = ref(null)

function groupByColor(colors) {
  const map = new Map()
  for (const c of colors) map.set(c, (map.get(c) || 0) + 1)
  return [...map.entries()].map(([color, count]) => ({ color, count }))
}

function drawChart() {
  const canvas = canvasEl.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const sectors = groupByColor(props.colors)
  const angle = (2 * Math.PI) / props.colors.length
  const c = props.size / 2
  // В оригинальном прототипе обводка секторов — белая (цвет светлой темы),
  // не тёмно-чернильная — иначе на ярких секторах она выглядит грязно.
  const strokeColor = getComputedStyle(document.documentElement).getPropertyValue('--color-bg').trim() || '#FAF9F4'

  let currentAngle = 0
  for (const sector of sectors) {
    const start = currentAngle
    currentAngle += sector.count * angle
    ctx.beginPath()
    ctx.moveTo(c, c)
    ctx.arc(c, c, c, start, currentAngle)
    ctx.closePath()
    ctx.fillStyle = sector.color
    ctx.fill()
    ctx.strokeStyle = strokeColor
    ctx.lineWidth = 2
    ctx.stroke()
  }
}

onMounted(drawChart)
</script>

<template>
  <div class="cluster-icon" :style="{ width: size + 'px', height: size + 'px' }">
    <canvas ref="canvasEl" :width="size" :height="size" />
    <span class="cluster-count">{{ colors.length }}</span>
  </div>
</template>

<style scoped>
.cluster-icon { position: relative; cursor: pointer; border-radius: 50%; filter: drop-shadow(0 2px 6px rgba(42,33,24,0.35)); }
canvas { display: block; border-radius: 50%; }
.cluster-count {
  position: absolute; inset: 22%; display: flex; align-items: center; justify-content: center;
  background: var(--color-surface); border-radius: 50%; font-family: var(--font-body); font-weight: 700;
  font-size: 15px; color: var(--color-ink);
}
</style>
