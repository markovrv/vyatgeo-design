import { ref } from 'vue'
import { API_BASE_URL } from '@/config/api'
import { t } from '@/i18n'

const siteName = ref(t('common.siteName'))

export function useSiteInfo() {
  async function fetchSiteInfo() {
    try {
      const res = await fetch(API_BASE_URL)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      if (data?.name) siteName.value = data.name
    } catch {
      // оставляем фолбэк из strings.json
    }
  }

  return { siteName, fetchSiteInfo }
}
