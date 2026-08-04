<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useHeaderScroll } from '@/composables/useHeaderScroll'

const props = defineProps({ active: { type: String, default: 'home' }, solid: { type: Boolean, default: false } })

const { scrolled, isMobile } = useHeaderScroll()
const menuOpen = ref(false)

const isSolid = computed(() => props.solid || scrolled.value)

const navItems = [
  ['home', 'Главная', '/'],
  ['victory', 'Лица Победы', '/victory'],
  ['ethnography', 'Этнография', '/ethnography'],
  ['cities', 'Города', '/cities'],
  ['nature', 'Природа', '/nature'],
  ['archeology', 'Археология', '/archeology'],
  ['architecture', 'Архитектура', '/architecture'],
  ['about', 'О проекте', '/about'],
]

function closeMenu() { menuOpen.value = false }
function openMenu() { menuOpen.value = true }

function onEscape(e) { if (e.key === 'Escape' && menuOpen.value) closeMenu() }
onMounted(() => window.addEventListener('keydown', onEscape))
onUnmounted(() => window.removeEventListener('keydown', onEscape))
</script>

<template>
  <header class="site-header" :class="{ '--scrolled': isSolid }">
    <div class="header-inner">
      <RouterLink to="/" class="logo-link">
        <svg class="logo-icon" viewBox="0 0 40 40" width="40" height="40" fill="none">
          <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2" fill="none" />
          <path d="M12 30c0-6 4-10 8-10s8 4 8 10" stroke="currentColor" stroke-width="2" fill="none" />
          <circle cx="20" cy="12" r="4" fill="currentColor" />
          <circle cx="20" cy="12" r="1.5" :fill="isSolid ? '#FAF9F4' : '#2A2118'" />
          <circle cx="28" cy="10" r="2" fill="currentColor" opacity="0.6" />
          <circle cx="12" cy="10" r="2" fill="currentColor" opacity="0.6" />
        </svg>
        <span class="logo-text">Наследие Вятки</span>
      </RouterLink>

      <nav v-if="!isMobile" class="desktop-nav">
        <RouterLink v-for="[key, label, to] in navItems" :key="key" :to="to" class="nav-link" :class="{ active: key === active }">
          {{ label }}
        </RouterLink>
      </nav>

      <button v-if="isMobile" class="burger" aria-label="Открыть меню" :aria-expanded="menuOpen" @click="openMenu">
        <span class="burger-line" />
        <span class="burger-line" />
        <span class="burger-line" />
      </button>
    </div>
  </header>

  <div class="overlay" :class="{ '--visible': menuOpen }" @click="closeMenu" />

  <nav class="sidebar" :class="{ '--open': menuOpen }">
    <button class="sidebar-close" aria-label="Закрыть" @click="closeMenu">&times;</button>
    <RouterLink v-for="[key, label, to] in navItems" :key="key" :to="to" class="sidebar-link" :class="{ active: key === active }" @click="closeMenu">
      {{ label }}
    </RouterLink>
  </nav>
</template>

<style scoped>
.site-header {
  position: fixed; top: 0; left: 0; width: 100%; z-index: var(--z-header, 1000); height: 80px;
  display: flex; align-items: center; padding: 0 var(--space-3);
  transition: background 300ms, border-color 300ms, box-shadow 300ms;
  background: rgba(42,33,24,0.28); border-bottom: 1px solid transparent;
  backdrop-filter: blur(8px);
}
.site-header.--scrolled {
  background: var(--color-bg); border-bottom: 1px solid var(--color-ochre);
  box-shadow: 0 2px 20px rgba(42,33,24,0.15); backdrop-filter: none;
}
.header-inner {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%; max-width: 1200px; margin: 0 auto;
}
.logo-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.logo-icon { width: 40px; height: 40px; color: var(--color-ochre); }
.logo-text { font-family: var(--font-display); font-size: 22px; color: var(--color-oak); }
.--scrolled .logo-text { color: var(--color-ink); }

.desktop-nav { display: flex; gap: var(--space-3); }
.nav-link {
  font-family: var(--font-body); font-size: 15px; color: var(--color-oak);
  padding-bottom: 4px; border-bottom: 2px solid transparent; text-decoration: none; white-space: nowrap;
  transition: color 300ms, border-color 300ms;
}
.--scrolled .nav-link { color: var(--color-ink); }
.nav-link:hover { color: var(--color-ochre); }
.nav-link.active { color: var(--color-ochre); border-bottom-color: var(--color-ochre); }

.burger {
  display: flex; flex-direction: column; justify-content: center; gap: 5px;
  width: 32px; height: 32px; background: transparent; border: none; cursor: pointer; padding: 4px;
}
.burger-line { display: block; width: 100%; height: 2px; background: var(--color-ochre); border-radius: var(--radius); }

.overlay {
  position: fixed; inset: 0; background: rgba(42,33,24,0.4); z-index: var(--z-overlay);
  opacity: 0; visibility: hidden; transition: opacity 300ms, visibility 300ms;
}
.overlay.--visible { opacity: 1; visibility: visible; }

.sidebar {
  position: fixed; top: 0; right: 0; width: min(280px, 80vw); height: 100vh;
  background: var(--color-bg); z-index: var(--z-sidebar);
  transform: translateX(100%); transition: transform 300ms var(--ease-out);
  padding: var(--space-3); display: flex; flex-direction: column; gap: var(--space-2);
  box-shadow: -4px 0 20px rgba(42,33,24,0.1); overflow-y: auto;
}
.sidebar.--open { transform: translateX(0); }

.sidebar-close {
  align-self: flex-end; background: transparent; border: none;
  font-size: 24px; color: var(--color-ochre); cursor: pointer;
  padding: var(--space-1) 12px; min-width: 44px; min-height: 44px;
}
.sidebar-link {
  font-family: var(--font-body); font-size: 16px; color: var(--color-ink);
  padding: var(--space-1) 0; border-bottom: 1px solid var(--color-oak);
  text-decoration: none; display: block;
}
.sidebar-link.active { color: var(--color-ochre); }
</style>
