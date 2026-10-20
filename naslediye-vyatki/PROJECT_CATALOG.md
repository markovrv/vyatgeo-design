# Каталог проекта «Наследие Вятки»

Справочная карта проекта — что где лежит, как устроен продукт и как всё
связано с живым сервером. Цель файла — чтобы новая сессия (после потери
контекста) могла сориентироваться за 5 минут, не перечитывая всю историю
чата. Подробные паттерны, грабли и конвенции кода — в `CLAUDE.md` (это
"правила работы", этот файл — "карта местности"); сюда они не дублируются.

Актуально на: 2026-10-20.

## 1. Что это

Статический изначально, затем переведённый на живой WordPress REST API
сайт к 90-летию Кировской области (1936–2026): 6 каталожных разделов
(герои, этнография, города/история, природа, археология, архитектура) +
обычные WP-страницы/записи/комментарии/меню.

- **Прод-сайт**: `https://testsite.vyatgeo.ru/`
- **Репозиторий**: `github.com/markovrv/vyatgeo-design` (ветка `main`)
- **Сервер**: `markovrv.ru`, путь к сайту —
  `/var/www/fastuser/data/www/testsite.vyatgeo.ru/` (панель хостинга —
  аккаунт `fastuser`, владелец файлов темы/плагинов — `fastuser:fastuser`,
  не `root`, даже если деплой был по SSH от root).

## 2. Структура репозитория

Корень репозитория (`vyatgeo-design/`) — два слоя: старый статический
HTML-прототип в корне (`index.html`, `*.html`, `design-system.html`,
`CLAUDE.md` в корне) — **только референс дизайна, не дорабатывается** — и
рабочая директория `naslediye-vyatki/` с Vue-реализацией, в которой
реально ведётся разработка. Этот файл и всё ниже — про `naslediye-vyatki/`.

```
naslediye-vyatki/
  CLAUDE.md                 # инструкции для Claude: паттерны, грабли, конвенции (детально)
  PROJECT_CATALOG.md         # этот файл — карта проекта
  README.md                  # стандартный Vite-README (служебный)
  PRODUCT.md, PRODUCT_VUE.md # продуктовое описание разделов сайта
  DESIGN.md                  # дизайн-система, паттерны карточек/кнопок/отступов
  about.md                    # черновик текста страницы «О проекте»
  ATTRACTION_INTEGRATION_PLAN.md, ETHNOGRAPHY_INTEGRATION_PLAN.md
                               # исторические ТЗ на интеграцию конкретных модулей (архив)
  index.html                  # SPA shell: window.__API_BASE_URL__/__YANDEX_MAPS_API_KEY__/...
  vite.config.js, package.json
  src/                         # Vue 3 + Vite фронтенд — см. раздел 3
  api/                         # рабочая копия исходников 7 WP-плагинов + разовые Python-скрипты
  wp-theme/                    # SPA-тема WordPress + Docker-окружение для локального теста
  public/polygons/*.geojson    # исторические полигоны границ Вятки/Хлынова для карты
  Вятушка/                     # сырые исходники для разовых импортов (Excel/фото), в .gitignore,
                                # не часть кодовой базы — см. Вятушка/PLAN.md внутри
  dist/, node_modules/         # билд и зависимости, в .gitignore
```

### `src/` — фронтенд (подробное дерево)

```
src/
  main.js                 # tokens.css → base.css → Vue app + router + vue-yandex-maps
  App.vue, style.css
  router/index.js         # маршруты (см. раздел 3.3) + резолвер /archives/* → ID
  config/
    api.js                 # API_BASE_URL из window.__API_BASE_URL__
    maps.js                 # YANDEX_MAPS_API_KEY
    attraction.js            # ATTRACTION_NEARBY_RADIUS_KM
  i18n/
    index.js                 # t()/useStrings() — чтение strings.json + переопределения из wp-admin
    strings.json              # дефолтные строки UI (дерево по components/views/layouts)
  composables/              # один use*.js на модуль/фичу — см. таблицы в разделе 3
  components/                # переиспользуемые: CatalogLayout, FilterBar, MultiSelectFilter,
                              # Pagination, ImageLightbox, CommentsSection, *Map.vue, *Controls.vue…
  views/                     # по {Module}View.vue + {Module}ItemView.vue на раздел
  layouts/                   # SiteHeader.vue, SiteFooter.vue
  styles/
    tokens.css                # дизайн-токены (:root) — единственный источник цветов/отступов
    base.css                   # reset, focus-visible, prefers-reduced-motion
  assets/img/                 # hero-картинки разделов, видео Кремля
```

### `api/` — бэкенд-плагины (рабочая копия) + служебные скрипты

```
api/
  attraction/            # «Архитектура и скульптура г. Кирова» — CPT attraction
  artifact-finder/        # «Этнографическая коллекция» — CPT finding
  history/                 # «История города…» — CPT history + таксономия city
  archeology/               # «Памятники археологии Кировской области» — CPT archeology
  geroi-vyatki/              # «Герои Вятского края» — CPT hero
  oopt-kirov/                 # «Памятники природы Кировской области» — CPT oopt
  geopoints/                   # «Геобаза археологических изысканий» — CPT geopoints
                                 # (НЕ интегрирован во фронтенд SPA, живёт только в wp-admin)
  docs/*.md                    # контракты REST-эндпоинтов по каждому плагину
  старые версии плагинов/        # архив, не используется
  Geroi.xlsx                    # исходные данные первых 215/216 героев (api/import_geroi_oopt.py)
  import_geroi_oopt.py           # разовый импорт героев/ООПТ из Geroi.xlsx (образец для всех прочих)
  import_geroi_novye_biografii.py # импорт +130 героев из Вятушка/
  import_history_urzhum_yaransk.py # импорт ленты истории Уржума и Яранска из Вятушка/
  spread_duplicate_hero_coords.py  # разовая правка: раздвинул 271 героя с одинаковыми координатами
  classify_hero_connection.py      # классификатор hero_connection → таксономия hero_connection_type
  classify_findings.py, upload_classification.py # аналогичная классификация для finding (этнография)
  hero_coord_spread_log.json, hero_connection_classify_log.json, classification_result.json
                                     # логи разовых скриптов (id/было/стало) — на случай сверки
  prompt-*.md                        # исходные ТЗ на модули (архив, для истории решений)
```

Все `import_*`/`classify_*`/`spread_*` скрипты — один и тот же паттерн:
`--dry-run`/`--limit N`, REST-запросы напрямую на `testsite.vyatgeo.ru`,
креды WordPress (`WORDPRESS_URL`/`USERNAME`/`PASSWORD`) — константы в
начале каждого файла (одни и те же во всех, см. `import_geroi_oopt.py`
как образец). Не хранить эти креды где-то ещё отдельно — смотреть там.

### `wp-theme/` — тема WordPress (SPA shell) + Docker

```
wp-theme/
  naslediye-vyatki-theme/
    functions.php          # рендер dist/index.html, Customizer API, редиректы permalink→SPA,
                            # автосоздание меню/страницы «about», post-thumbnails
    menu-api.php             # REST nv/v1/menu/{location}
    admin/
      import.php              # Инструменты → Import from API (копирование контента с др. WP)
      i18n-strings.php         # админка переопределения строк UI (см. раздел 3.5)
    dist/                     # собранный билд (npm run build:theme) — НЕ редактировать руками
    style.css, index.php       # служебные файлы темы WP
  docker-compose.yml, .env, ensure-htaccess.sh # локальный WordPress+MariaDB на :8080 (см. CLAUDE.md)
  php-uploads.ini
```

## 3. Структура продукта (архитектура)

### 3.1 Базовый фреймворк

- **Бэкенд**: обычный самостоятельный **WordPress** на `testsite.vyatgeo.ru`
  (не headless CMS-as-a-service, не WP как npm-пакет) — классические PHP-
  плагины + кастомная тема. Все данные (посты, таксономии, медиа,
  комментарии, меню, страницы) живут в этом WordPress и отдаются через
  `wp-json/` REST API.
- **Фронтенд**: **Vue 3 + Vite** SPA (`vue-router`, `vue-yandex-maps`),
  без SSR — полностью клиентский рендеринг. Собирается в статику
  (`dist/`) и подкладывается внутрь темы WordPress.
- **Склейка**: тема WordPress не рендерит ничего своего — на любой фронтовый
  URL отдаёт `dist/index.html` (SPA shell), `wp-admin`/`wp-login.php`/
  `wp-json/` тема не трогает. То есть WordPress тут — админка + БД + REST
  API, а не генератор страниц.

### 3.2 Плагины (бэкенд, `api/*`)

| Плагин (папка) | Название в WP | CPT | Таксономии | Кастомный REST | Статус во фронтенде |
|---|---|---|---|---|---|
| `attraction` | Архитектура и скульптура г. Кирова | `attraction` | — | `attraction/v1/*` | ✅ интегрирован (`/architecture`) |
| `artifact-finder` | Этнографическая коллекция | `finding` | `finding_material`, `finding_origin`, `finding_receipt_time`, `finding_creation_time`, `finding_type` | `findings/v1/*` | ✅ интегрирован (`/ethnography`) |
| `history` | История города Хлынова/Вятки/Кирова | `history` | `city` (городская группировка) | `history/v1/*`, `custom/v1/history` | ✅ интегрирован (`/cities`) |
| `archeology` | Памятники археологии Кировской области | `archeology` | `archeology_era`, `archeology_type` | `archeology/v1/*` | ✅ интегрирован (`/archeology`) |
| `geroi-vyatki` | Герои Вятского края | `hero` | `hero_status`, `hero_connection_type` | `hero/v1/*` | ✅ интегрирован (`/victory`) |
| `oopt-kirov` | Памятники природы Кировской области | `oopt` | `oopt_district`, `oopt_profile`, `oopt_species` | `oopt/v1/*` | ✅ интегрирован (`/nature`) |
| `geopoints` | Геобаза археологических изысканий | `geopoints` | `headman`, `deep`, `workdate`, `worktype` | `geopoints/v1`, `geopoints/v2` | ❌ не подключён к SPA (только wp-admin) |

Плюс сквозные стандартные сущности WP (не отдельные плагины, встроенный
функционал + тема): **Pages** (`usePage.js`), **Posts/«Новости»**
(`usePosts.js`), **Comments** (`useComments.js`), **Menu**
(`useMenu.js`/`menu-api.php`).

Паттерн каждого плагина (кроме `geopoints`, который старый/не доработан
под этот паттерн) — см. «REST-паттерны на бэкенде» в `CLAUDE.md`: bulk-
список без пагинации с облегчёнными полями → деталь по id через штатный
`wp/v2/{cpt}/{id}?_embed=1` → `/adjacent` и `/nearby`/`/similar` с
ранжированием на сервере.

### 3.3 Фронтенд-модули (разделы SPA)

| Раздел | Маршруты | View | Composable | Карта |
|---|---|---|---|---|
| Главная | `/` | `IndexView.vue` | `useHistory.js` (вехи) | — |
| Лица Победы | `/victory`, `/victory/:id` | `HeroesView.vue`/`HeroItemView.vue` | `useHeroes.js` | `HeroMap.vue` |
| Этнография | `/ethnography`, `/ethnography/:id` | `EthnographyView.vue`/`Item` | `useFindings.js` | — (нет гео) |
| Города (История) | `/cities`, `/cities/:slug`, `/cities/:slug/event/:id` | `HistoricCitiesView.vue`/`CityTimelineView.vue`/`CityEventView.vue` | `useHistory.js` | `HistoryCitiesMap.vue` |
| Природа | `/nature`, `/nature/:id` | `NatureView.vue`/`Item` | `useOopt.js` | `NatureMap.vue` |
| Археология | `/archeology`, `/archeology/:id` | `ArcheologyView.vue`/`Item` | `useArcheology.js` | `ArcheologyMap.vue` |
| Архитектура | `/architecture`, `/architecture/:id` | `ArchitectureView.vue`/`ArchitectureObjectView.vue` | `useAttractions.js` | `AttractionMap.vue` |
| О проекте | `/about` | `PageView.vue` (slug зафиксирован) | `usePage.js` | — |
| Произвольная WP-страница | `/page/:slug` | `PageView.vue` | `usePage.js` | — |
| Новости | `/news`, `/news/:id` | `NewsView.vue`/`NewsItemView.vue` | `usePosts.js` | — |

Короткие WP-пермалинки (`?p=123`, `/archives/attraction/slug` и т.п.)
302-редиректятся на эти SPA-маршруты в `functions.php`
(`nv_spa_resolve_wp_permalink_redirect`), а `/archives/*` — ещё и во
фронтовом `router.beforeEach` (см. `src/router/index.js`).

### 3.4 Настройки (Customizer)

**Внешний вид → Настроить → Конфигурация приложения** — 3 настройки,
читаются `functions.php` → вставляются в `<head>` `dist/index.html` как
`window.__*__`:

| Настройка | `theme_mod` | Дефолт |
|---|---|---|
| API Base URL | `nv_api_base_url` | `https://testsite.vyatgeo.ru/wp-json/` |
| Yandex Maps API Key | `nv_yandex_maps_api_key` | `47e70ac9-78de-4c21-9fba-ffd8e5855ee6` |
| Радиус «Объекты рядом» (км) | `nv_attraction_nearby_radius_km` | `1.5` |

### 3.5 i18n (переопределение строк UI)

`src/i18n/strings.json` — дефолтные строки (дерево по
`components`/`views`/`layouts`/`common`). `wp-admin` → страница
переопределения строк (`admin/i18n-strings.php`) позволяет редактору
менять текст UI без правки кода/пересборки — хранится в WP (опция),
отдаётся поверх дефолтов из `strings.json`. `src/i18n/index.js` —
`t()`/`useStrings()`, мёрджит дефолты + переопределения.

## 4. Текущее состояние данных (снэпшот, актуально на дату файла)

| CPT | Записей |
|---|---|
| `hero` (Герои) | 345 |
| `oopt` (Природа) | 17 |
| `archeology` | 10 |
| `attraction` (Архитектура) | 423 |
| `finding` (Этнография) | 767 |
| `history` (события городов) | 866 (в т.ч. 100 Уржум + 100 Яранск — добавлены из `Вятушка/`) |

Таксономии героев: `hero_status` (Историческая личность / Современник /
Жив — 3 термина, расширено с исходных 2 при импорте `Вятушка`),
`hero_connection_type` (9 терминов, нехиерархическая, мультиселект —
классифицирована из свободного текста `hero_connection`, см.
`api/classify_hero_connection.py`).

Это снэпшот на момент написания — для актуальных цифр смотреть живой
`wp-json/wp/v2/{cpt}?per_page=1` (заголовок `X-WP-Total`), не верить
числам в этом файле бессрочно.

## 5. Деплой и доступы

- **Фронтенд**: `rm -rf dist wp-theme/naslediye-vyatki-theme/dist && npm run build:theme`
  → залить `wp-theme/naslediye-vyatki-theme/dist/` (и `i18n/strings.json`)
  в `.../wp-content/themes/naslediye-vyatki-theme/` на проде. Обязательно
  **сначала очистить** папку `dist/` на сервере (`rm -rf` перед заливкой) —
  иначе старые content-hash чанки копятся и неясно, какой реально отдаётся
  (см. подробный разбор этой и ещё одной грабли в `CLAUDE.md`, раздел
  Docker — тот же принцип актуален и для прод-сервера).
- **Бэкенд-плагины**: правки `api/*.php` переносятся на сервер вручную
  (по SSH/SCP в `.../wp-content/plugins/{slug}/`), не автоматически.
  Перед деплоем проверять `php -l` (версии PHP есть в
  `/c/OSPanel/modules/php/`, актуальный синтаксис — PHP 8.x).
- **Локальный Docker** (для теста темы без доступа к проду) —
  `wp-theme/docker-compose.yml`, подробная инструкция (включая обе
  известные грабли с путями в Git Bash и неочищаемым `dist/`) — в
  `CLAUDE.md`, раздел «Тема WordPress (SPA Shell) → Docker».
- **Доступ к проду по SSH** (root, `markovrv.ru`) — использовался для
  деплоя в предыдущих сессиях (PuTTY `plink`/`pscp`, т.к. `sshpass`/
  `rsync` не установлены в этом окружении); креды в этом репозитории
  **не хранятся** — если нужен новый деплой по SSH, спросить у
  пользователя заново.
- **Доступ к WordPress REST API** (пользователь `admin`) — захардкожен в
  начале каждого `api/import_*.py`/`api/classify_*.py`/`api/spread_*.py`
  (уже закоммичен в репозиторий, см. раздел 2 выше) — не отдельная тайна,
  но и не нужно плодить ещё одну копию в новых файлах, ссылаться на
  существующий скрипт.

## 6. Где смотреть подробности

- **Как написан код / на какие грабли не наступать повторно** — `CLAUDE.md`
  (структура композаблов, паттерны каталога/карты/страницы объекта,
  REST-паттерны на бэкенде, i18n, сквозные фичи — всё расписано построчно
  с примерами и найденными багами).
- **Что видит пользователь на каждой странице** — `PRODUCT.md` (кратко),
  `PRODUCT_VUE.md` (подробно, по факту текущей Vue-реализации).
  `DESIGN.md` — визуальный язык (карточки/кнопки/отступы/цвета).
- **Контракты REST-эндпоинтов** каждого плагина — `api/docs/*.md`.
- **Откуда взялись последние 130 героев и события Уржума/Яранска,
  какие решения были согласованы по спорным местам источника** —
  `Вятушка/PLAN.md` (сама папка `Вятушка/` в `.gitignore`, но план —
  важный источник контекста по уже принятым решениям).
- **История решений по интеграции конкретных модулей** —
  `ATTRACTION_INTEGRATION_PLAN.md`, `ETHNOGRAPHY_INTEGRATION_PLAN.md`,
  `api/prompt-*.md` (исходные ТЗ) — архивные, но полезны для понимания
  «почему именно так».

## 7. Недавние крупные доработки (для ориентира, не исчерпывающе)

Хронологию по коммитам — `git log --oneline` в `naslediye-vyatki/`.
Коротко, что сделано в последних сессиях поверх базовой интеграции 6
модулей:

1. Интернационализация SPA через `strings.json` + переопределение из
   wp-admin.
2. Импорт 130 новых героев и ленты истории Уржума/Яранска из сырых
   материалов `Вятушка/` (Excel/docx/фото) — с решением вопросов по
   схеме статусов, координатам городов, обложкам, дублям данных.
3. Фикс обрезки лиц на фото героев в карточках/картах (`background-
   position`) + добавлено фото на страницу героя.
4. Раздвижка 271 героя с полностью одинаковыми координатами (ломало
   кластеризацию на карте).
5. Классификация `hero_connection` (свободный текст) в новую таксономию
   `hero_connection_type` (9 категорий, мультиселект) + фильтр в
   каталоге героев.

Все пункты 2–5 задеплоены на `testsite.vyatgeo.ru` и закоммичены в
`main`.
