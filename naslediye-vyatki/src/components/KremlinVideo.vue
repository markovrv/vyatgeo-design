<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import videoSrc from '@/assets/img/kremlin.mp4'

const videoEl = ref(null)
const soundOn = ref(false)

const mutedIcon = `<polygon points="4,9 8,9 12,5 12,19 8,15 4,15" fill="#E8DFC8" stroke="none"></polygon><line x1="16" y1="8" x2="22" y2="16"></line><line x1="22" y1="8" x2="16" y2="16"></line>`
const unmutedIcon = `<polygon points="4,9 8,9 12,5 12,19 8,15 4,15" fill="#E8DFC8" stroke="none"></polygon><path d="M15.5 8.5a5 5 0 0 1 0 7"></path><path d="M18.5 6a9 9 0 0 1 0 12"></path>`

let observer = null

function toggleSound() {
  if (!videoEl.value) return
  videoEl.value.muted = !videoEl.value.muted
  soundOn.value = !videoEl.value.muted
}

onMounted(() => {
  const video = videoEl.value
  if (!video) return
  video.muted = true
  video.playbackRate = 0.5
  video.addEventListener('timeupdate', () => {
    if (video.currentTime >= 8) video.currentTime = 0
  })
  observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) video.play().catch(() => {})
      else video.pause()
    })
  }, { threshold: 0.3 })
  observer.observe(video)
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})
</script>

<template>
  <div class="video-wrap">
    <video ref="videoEl" :src="videoSrc" muted playsinline loop class="video" aria-label="Трифонов монастырь — вид на Кремль" />
    <button class="sound-btn" :aria-label="soundOn ? 'Выключить звук' : 'Включить звук'" :title="soundOn ? 'Выключить звук' : 'Включить звук'" @click="toggleSound">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#E8DFC8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="soundOn ? unmutedIcon : mutedIcon" />
    </button>
  </div>
</template>

<style scoped>
.video-wrap { position: relative; aspect-ratio: 2/3; overflow: hidden; border-radius: var(--radius); border: 1.5px dashed var(--color-border); filter: sepia(20%) }
.video { width: 100%; height: 100%; object-fit: cover; display: block }
.sound-btn {
  position: absolute; right: 12px; bottom: 12px; width: 44px; height: 44px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(42,33,24,0.55); border: 1px solid rgba(232,223,200,0.5);
  border-radius: 50%; cursor: pointer; backdrop-filter: blur(2px);
  transition: background 150ms, transform 150ms;
}
.sound-btn:hover { background: rgba(42,33,24,0.8); transform: scale(1.08) }
</style>
