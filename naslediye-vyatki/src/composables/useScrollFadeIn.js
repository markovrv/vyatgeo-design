import { ref, onMounted } from 'vue'

export function useScrollFadeIn() {
  const mounted = ref(false)
  onMounted(() => setTimeout(() => mounted.value = true, 50))

  function fadeStyle(i = 0) {
    return {
      opacity: mounted.value ? 1 : 0,
      transform: `translateY(${mounted.value ? 0 : 30}px)`,
      transition: `opacity 400ms ${i * 60}ms, transform 400ms ${i * 60}ms cubic-bezier(0.16,1,0.3,1)`,
    }
  }

  return { fadeStyle }
}
