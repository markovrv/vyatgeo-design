// Радиус поиска (км) для блока "Объекты рядом" на странице объекта архитектуры —
// задаётся глобально в index.html (window.__ATTRACTION_NEARBY_RADIUS_KM__),
// по тому же принципу, что и API_BASE_URL/YANDEX_MAPS_API_KEY, и уходит на
// сервер параметром запроса (см. useAttractionNearby в useAttractions.js).
export const ATTRACTION_NEARBY_RADIUS_KM = window.__ATTRACTION_NEARBY_RADIUS_KM__ || 1.5
