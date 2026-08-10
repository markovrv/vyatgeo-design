// Единая точка чтения адреса API для всех модулей.
// Значение задаётся глобально в index.html (window.__API_BASE_URL__),
// чтобы адрес можно было менять на сервере без пересборки бандла.
export const API_BASE_URL = window.__API_BASE_URL__ || 'https://testsite.vyatgeo.ru/wp-json/'

// Nonce для авторизованных REST-запросов от имени залогиненного в WP
// пользователя (X-WP-Nonce). Кука сессии браузер шлёт на тот же домен
// автоматически независимо от того, ждём мы этого или нет — безnonce WP
// либо не распознаёт пользователя вовсе (см. /wp/v2/users/me), либо отдаёт
// 403 на запись. Подставляется темой в dist/index.html только когда
// is_user_logged_in() — см. nv_spa_user_script() в functions.php; для
// анонимного посетителя window.__WP_REST_NONCE__ не определена.
export const REST_NONCE = window.__WP_REST_NONCE__ || null
