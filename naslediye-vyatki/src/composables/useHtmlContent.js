import { API_BASE_URL } from '@/config/api'

export const SITE_URL = API_BASE_URL.replace(/wp-json\/?$/, '')

const entityDecoder = typeof document !== 'undefined' ? document.createElement('textarea') : null

export function decodeEntities(str) {
  if (!str || !entityDecoder) return str || ''
  entityDecoder.innerHTML = str
  return entityDecoder.value.replace(/\s+/g, ' ').trim()
}

export function resolveRelativeUrls(html) {
  return (html || '').replaceAll('../../', SITE_URL)
}
