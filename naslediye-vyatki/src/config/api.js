// Единая точка чтения адреса API для всех модулей.
// Значение задаётся глобально в index.html (window.__API_BASE_URL__),
// чтобы адрес можно было менять на сервере без пересборки бандла.
export const API_BASE_URL = window.__API_BASE_URL__ || 'https://testsite.vyatgeo.ru/wp-json/'
