<script setup>
import { onMounted } from 'vue'
import { useResolvedNav } from '@/composables/useMenu'
import { useSiteInfo } from '@/composables/useSiteInfo'
import { useStrings } from '@/i18n'

const { t } = useStrings()
const { siteName, fetchSiteInfo } = useSiteInfo()

const HARDCODED_FOOTER_NAV = [
  ['home', t('layouts.SiteFooter.navHome'), '/'],
  ['victory', t('layouts.SiteFooter.navVictory'), '/victory'],
  ['ethnography', t('layouts.SiteFooter.navEthnography'), '/ethnography'],
  ['cities', t('layouts.SiteFooter.navCities'), '/cities'],
  ['nature', t('layouts.SiteFooter.navNature'), '/nature'],
  ['archeology', t('layouts.SiteFooter.navArcheology'), '/archeology'],
  ['architecture', t('layouts.SiteFooter.navArchitecture'), '/architecture'],
  ['about', t('layouts.SiteFooter.navAbout'), '/about'],
]

const { navItems, load: loadMenu } = useResolvedNav('spa_footer', HARDCODED_FOOTER_NAV)

onMounted(() => { loadMenu(); fetchSiteInfo() })
</script>

<template>
  <footer class="site-footer">
    <svg class="footer-wave" viewBox="0 0 1200 24" preserveAspectRatio="none">
      <rect x="0" y="10" width="100%" height="2" fill="currentColor" opacity="0.3" />
      <circle cx="100" cy="12" r="4" fill="currentColor" />
      <circle cx="300" cy="12" r="3" fill="currentColor" opacity="0.7" />
      <circle cx="500" cy="12" r="5" fill="currentColor" />
      <circle cx="700" cy="12" r="3" fill="currentColor" opacity="0.7" />
      <circle cx="900" cy="12" r="4" fill="currentColor" />
      <circle cx="1100" cy="12" r="3" fill="currentColor" opacity="0.7" />
    </svg>

    <div class="footer-grid">
      <div>
        <h4 class="footer-brand">
          <svg viewBox="0 0 40 40" width="24" height="24" fill="var(--color-ochre)">
            <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2" fill="none" />
            <path d="M12 30c0-6 4-10 8-10s8 4 8 10" stroke="currentColor" stroke-width="2" fill="none" />
            <circle cx="20" cy="12" r="4" fill="currentColor" />
          </svg>
          {{ siteName }}
        </h4>
        <p class="footer-desc">{{ t('layouts.SiteFooter.desc') }}</p>
      </div>
      <div>
        <strong class="footer-heading">{{ t('layouts.SiteFooter.sections') }}</strong>
        <div class="footer-links">
          <template v-for="item in navItems" :key="item.key">
            <RouterLink v-if="item.spaRoute" :to="item.to">{{ item.label }}</RouterLink>
            <a v-else :href="item.to" :target="item.external ? (item.target || '_blank') : (item.target || undefined)">{{ item.label }}</a>
          </template>
        </div>
      </div>
      <div>
        <p class="footer-desc">{{ t('layouts.SiteFooter.supportText') }}</p>
      </div>
    </div>

    <div class="footer-bottom">
      {{ t('layouts.SiteFooter.copyright') }}
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  background: var(--color-ink); border-top: 2px solid var(--color-ochre);
  color: var(--color-oak); position: relative;
}
.footer-wave { display: block; width: 100%; height: 24px; fill: var(--color-ochre); }
.footer-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);
  padding: var(--space-4) var(--space-3) var(--space-3); max-width: 1200px; margin: 0 auto;
}
@media (max-width: 449px) {
  .footer-grid { grid-template-columns: 1fr; row-gap: 32px; }
  .footer-grid > div:not(:first-child) { border-top: 1px solid rgba(232,223,200,0.2); padding-top: var(--space-3); }
}
.footer-brand {
  font-family: var(--font-display); font-size: 20px; color: var(--color-bg);
  margin: 0 0 var(--space-2); display: flex; align-items: center; gap: var(--space-1);
}
.footer-desc { font-size: 13px; line-height: 1.7; color: var(--color-oak); margin: 0; }
.footer-heading { font-size: 16px; color: var(--color-bg); display: block; }
.footer-links { margin-top: var(--space-1); display: flex; flex-direction: column; gap: 4px; }
.footer-links a { font-size: 13px; color: var(--color-oak); text-decoration: none; }
.footer-bottom {
  border-top: 1px solid rgba(232,223,200,0.2); padding: var(--space-2) var(--space-3);
  text-align: center; font-size: 12px; color: var(--color-oak); max-width: 1200px; margin: 0 auto;
}
</style>
