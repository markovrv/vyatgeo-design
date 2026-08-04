import { ref, onMounted, onUnmounted } from 'vue'

export function useHeaderScroll() {
  const scrolled = ref(false)
  const isMobile = ref(false)

  const checkScroll = () => { scrolled.value = window.scrollY > 80 }
  const checkResize = () => { isMobile.value = window.innerWidth < 1024 }

  onMounted(() => {
    checkScroll()
    checkResize()
    window.addEventListener('scroll', checkScroll, { passive: true })
    window.addEventListener('resize', checkResize)
  })

  onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll)
    window.removeEventListener('resize', checkResize)
  })

  return { scrolled, isMobile }
}
