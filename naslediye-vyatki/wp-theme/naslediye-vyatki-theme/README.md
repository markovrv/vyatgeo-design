# Наследие Вятки — SPA Shell (тема WordPress)

Тема отключает стандартный вывод WordPress (страницы, записи, архивы, поиск,
404) и вместо него отдаёт статическую прод-сборку Vue-приложения
`naslediye-vyatki` на любой фронтовый URL. Вся маршрутизация внутри
приложения — на клиенте (`vue-router`, `createWebHistory()` без `base`, см.
`src/router/index.js`), поэтому сервер должен отдавать один и тот же
`index.html` на любой такой URL, а дальше маршрут разбирает сам SPA.

`/wp-admin/`, `/wp-login.php`, `/wp-json/...` тема не трогает — эти запросы
не доходят до хука `template_redirect`, на котором она перехватывает вывод
(см. `functions.php`).

## Деплой

1. В корне проекта (`naslediye-vyatki/`) собрать прод-билд и скопировать его
   в папку темы:
   ```bash
   npm run build:theme
   ```
   Это `vite build` + копирование `dist/` в `wp-theme/naslediye-vyatki-theme/dist/`
   (скрипт `scripts/copy-dist-to-theme.mjs`).
2. Скопировать (или засимлинкать) папку `wp-theme/naslediye-vyatki-theme/` в
   `wp-content/themes/` на сервере WordPress.
3. Активировать тему: **Внешний вид → Темы → Наследие Вятки — SPA Shell**.
4. При каждом новом релизе фронтенда — повторить шаг 1 и обновить папку темы
   на сервере (`dist/` внутри темы — сгенерированная папка, в git не
   коммитится, см. `.gitignore` рядом).

## Настройка nginx

### Без изменений конфига — тоже работает

Тема сама умеет отдавать и `index.html`, и статику приложения (`/assets/…`,
`/polygons/…`) через PHP (`functions.php` → `nv_spa_serve_static_asset` /
`nv_spa_render_app`). Поэтому она уже работает с любым стандартным конфигом
WordPress без единой правки — если в vhost уже есть типовой блок:

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

то любой путь, для которого нет реального файла (а `/architecture/123`,
`/cities/kirov` и т.п. таковыми не являются), провалится в `index.php`, WP
догрузится как обычно и на `template_redirect` отдаст SPA. `/wp-admin/`,
`/wp-content/uploads/…`, `/wp-json/…` и прочие реальные пути `try_files`
найдёт как есть и до PHP-фолбэка темы дело не дойдёт — ничего в работе самого
WordPress не меняется.

Минус этого варианта — каждый запрос JS/CSS/картинок/geojson-полигонов
приложения (а не только HTML-страниц) целиком поднимает WordPress через
PHP-FPM, что на проде лишняя нагрузка и задержка. Для локальной проверки/
staging это нормально, для боевого сервера — см. следующий раздел.

### Рекомендуемый вариант для прода: статика мимо PHP

Т.к. Vite собирает бандл с абсолютными от корня путями (`/assets/…`), а
`vue-router` в history-режиме работает без `base`, ассеты сборки должны быть
доступны по тем же корневым путям, что видит браузер (`/assets/xxx.js`,
`/polygons/xxx.geojson`) — независимо от того, что физически лежат они в
`wp-content/themes/naslediye-vyatki-theme/dist/`. Отдать их напрямую через
`alias`, в обход PHP, — сильно быстрее (и позволяет включить
`expires`/`immutable`-кэш, т.к. в именах файлов Vite-хэш и при пересборке имя
меняется).

Добавить в существующий `server { … }` блок WordPress **до** общего
`location /` (важен порядок — `^~` даёт этим блокам приоритет над
regex-локациями наподобие `\.php$`, а не только над префиксными):

```nginx
# Замените на реальный путь до установки WordPress на сервере.
set $wp_root /var/www/testsite.vyatgeo.ru;
set $theme_dist $wp_root/wp-content/themes/naslediye-vyatki-theme/dist;

location ^~ /assets/ {
    alias $theme_dist/assets/;
    try_files $uri =404;
    access_log off;
    add_header Cache-Control "public, max-age=31536000, immutable";
}

location ^~ /polygons/ {
    alias $theme_dist/polygons/;
    try_files $uri =404;
    access_log off;
    add_header Cache-Control "public, max-age=31536000, immutable";
}

# Остальной фронт (в т.ч. /, /architecture/123, /cities/kirov и т.д.) —
# штатный WP-роутинг, index.html SPA отдаёт уже сама тема через PHP.
location / {
    try_files $uri $uri/ /index.php?$args;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # либо 127.0.0.1:9000 — как настроено на сервере
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

`nginx -t` не выдаёт `$wp_root`/`$theme_dist` как «неопределённые» ошибки,
но `set` внутри `server {}` требует модуля `ngx_http_rewrite_module`, который
в стандартной сборке nginx включён всегда — можно просто прописать полный
путь напрямую в `alias`, если так привычнее.

Важно: если когда-нибудь реальному WP-контенту (странице, посту, файлу в
`wp-content/uploads`) понадобится URL, начинающийся с `/assets/` или
`/polygons/`, эти два `location`-блока (как и PHP-фолбэк темы) перехватят
его первыми. На практике эти префиксы зарезервированы только под сборку
Vite и не пересекаются с URL WP (`/wp-content/…`, `/%postname%/` и т.п.).

## Проверка после настройки

- `/` , `/architecture`, `/architecture/123`, `/cities/kirov` — открывают
  SPA (одна и та же HTML-страница, дальше маршрутизирует сам Vue Router).
- `/assets/index-*.js`, `/polygons/*.geojson` — отдаются напрямую (в devtools
  видно `200` без прохода через PHP, если настроен вариант с `alias`).
- `/wp-admin/` — обычная админка WordPress, без изменений.
- `/wp-json/history/v1/cities` (и другие REST-роуты проекта) — отдают JSON
  как раньше, тема на них не влияет.
- `/wp-login.php` — обычная форма входа.

## Customizer API (конфигурация SPA)

Тема регистрирует секцию **Конфигурация приложения** в Customizer
(Внешний вид → Настроить). Настройки позволяют переопределить параметры
`window.__*__` из `dist/index.html` без правки самого файла:

| Настройка | `theme_mod` | Переменная |
|---|---|---|
| API Base URL | `nv_api_base_url` | `window.__API_BASE_URL__` |
| Yandex Maps API Key | `nv_yandex_maps_api_key` | `window.__YANDEX_MAPS_API_KEY__` |
| Радиус «Объекты рядом» (км) | `nv_attraction_nearby_radius_km` | `window.__ATTRACTION_NEARBY_RADIUS_KM__` |

При отдаче `index.html` тема вставляет `<script>`-блок со значениями из
`get_theme_mod()` перед `</head>`. Скрипт выполняется после дефолтного блока
из `index.html` и переопределяет переменные. Сохранение в Customizer
(`transport: 'refresh'`) перезагружает страницу — для SPA это ожидаемое
поведение.

## Импорт контента из удалённого API

Тема добавляет страницу **Инструменты → Import from API** для копирования
публичного контента с удалённого WordPress-сайта. Импорт идёт пошагово через
AJAX с отображением прогресса по каждому типу данных.

### Поддерживаемый контент

Чекбоксы сгруппированы по плагинам с parent-переключателями:

| Плагин | Таксономии | Объекты |
|---|---|---|
| Архитектура (`attraction`) | — | Объекты (достопримечательности) |
| Города (`history`) | Города (`city`) | События (лента времени) |
| Этнография (`finding`) | Типы, материалы, происхождение, время поступления, время создания | Находки |

Таксономии импортируются первыми — объекты могут ссылаться на уже существующие
термины.

### Опции

- **Dry run** — подсчёт элементов без создания
- **Skip existing** — пропуск по точному совпадению заголовка
- **Download images** — скачивает featured image, изображения из контента
  и обновляет `attraction_imgSrc` на локальный URL; относительные пути
  `../../wp-content/...` и `/wp-content/...` в контенте заменяются на
  локальные URL вложений

### Особенности реализации

- Пошаговый импорт по 5 объектов за AJAX-запрос с progress bar
- Кнопка «Отменить» останавливает процесс на серверной стороне
- Метаполя сохраняются через `update_post_meta()` после `wp_insert_post()`
  (плагины имеют `save_post`-хуки, затирающие поля при отсутствии `$_POST`)
- Относительные URL в контенте (`../../wp-content/`, `../wp-content/`,
  `/wp-content/`) разворачиваются в абсолютные URL источника, затем при
  включённой опции скачивания заменяются на локальные

## Docker (локальное тестирование)

В родительской папке `wp-theme/` лежит `docker-compose.yml` для локального
запуска WordPress + MariaDB на порту 8080:

```bash
cd wp-theme
docker compose up -d       # запуск
docker compose down         # остановка
docker compose down -v      # остановка + удаление БД
```

### Состав контейнера

- **Чистый WordPress 6** — без темы и кастомных плагинов, всё ставится
  вручную через админку (тема → zip-архив, плагины → zip-архивы `api/`)
- **`apache2-wrapper`** — обёртка запуска: `.htaccess` + `chown uploads`
  перед стартом Apache; имя начинается с `apache2`, чтобы WordPress
  entrypoint выполнил инициализацию файлов
- **`ensure-htaccess.sh`** — генерирует `.htaccess` с mod_rewrite и чинит
  права на `wp-content/uploads`
- **`php-uploads.ini`** — поднимает лимиты `upload_max_filesize` и
  `post_max_size` до 128M (для загрузки архива темы с билдом)
- **`docker-compose.yml`** — монтирует только `uploads` (volume) и
  стартовые скрипты, тема и плагины не монтируются

### Первый запуск

1. `docker compose up -d`
2. http://localhost:8080 → пройти установку WordPress
3. **Настройки → Постоянные ссылки → Название записи → Сохранить**
   (обязательно для работы REST API `/wp-json/`)
4. Установить тему из zip, активировать
5. Установить плагины из `api/` через админку
6. Импортировать контент: **Инструменты → Import from API**
