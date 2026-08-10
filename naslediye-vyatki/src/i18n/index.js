import strings from './strings.json'

const wpOverrides = (typeof window !== 'undefined' && window.__I18N_STRINGS__) ? window.__I18N_STRINGS__ : null

function getByPath(obj, path) {
  return path.split('.').reduce((acc, key) => (acc == null ? undefined : acc[key]), obj)
}

function interpolate(template, vars) {
  if (!vars) return template
  return template.replace(/\{(\w+)\}/g, (_, key) => (key in vars ? String(vars[key]) : `{${key}}`))
}

export function t(path, vars) {
  const value = wpOverrides?.[path] ?? getByPath(strings, path)
  if (typeof value !== 'string') {
    if (import.meta.env.DEV) {
      console.warn(`[i18n] Ключ не найден: ${path}`)
    }
    return path
  }
  return interpolate(value, vars)
}

export function useStrings() {
  return { t }
}
