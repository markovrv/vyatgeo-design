# Промпт для ИИ-агента: интеграция модулей «Лица Победы» и «Природа» с реальным API

## 0. Контекст и задача

Бэкенд для обоих модулей полностью готов, задеплоен на `testsite.vyatgeo.ru` и наполнен данными:

- **«Герои Вятского края»** (`api/geroi-vyatki/`, CPT `hero`, 215 записей) — контракт см. `api/docs/geroi-vyatki.md`.
- **«Памятники природы Кировской области»** (`api/oopt-kirov/`, CPT `oopt`, 17 записей) — контракт см. `api/docs/oopt-kirov.md`.

Оба плагина разработаны по образцу `attraction` — исходное ТЗ на них см. `api/prompt-geroi-oopt-plugins.md`. **Эта задача — только фронтенд** (`naslediye-vyatki/src/`). Правки в `api/*` не нужны; если при реализации обнаружится расхождение живого API с документацией — сообщить об этом, не чинить бэкенд-плагины самостоятельно без подтверждения (правки на `api/` переносятся на боевой сервер вручную по FTP, см. `CLAUDE.md`).

Четыре модуля уже интегрированы по единому паттерну: Архитектура (`attraction`, эталон карты/каталога), Этнография (`artifact-finder`, эталон `MultiSelectFilter` для многозначных таксономий), Города (`history`, эталон ленты без карты), Археология (`geopoints`-соседний модуль, самый свежий пример: 2 таксономии + карта с кластеризацией, недавно приведена к дизайну Архитектуры). Все паттерны подробно задокументированы в `CLAUDE.md`, раздел «Паттерны модулей с реальным API» — **прочитать целиком перед началом работы и следовать буквально**, не изобретать заново решения, которые там уже приняты (структура composable, module-level singleton состояния каталога, батч-апгрейд миниатюр, устройство карты/кластеризации/лайтбокса, структура страницы объекта, REST-нюансы).

**Основной шаблон для копирования** (прочитать все пять файлов целиком до начала работы):

- `src/composables/useArcheology.js` — эталон composable: `archeologyCatalogState` (module-level singleton), `useArcheologySites`, `useArcheologyThumbnails`, `useArcheologySite`, `useArcheologyAdjacent`, `useArcheologyNearby`.
- `src/views/ArcheologyView.vue` — каталог: `CatalogLayout` слоты, `FilterBar` для таксономий, floating/inline контролы, клиентская фильтрация+пагинация.
- `src/views/ArcheologyItemView.vue` — страница объекта: breadcrumb → intro → summary/content → prev/next → back-bar → nearby-сетка → комментарии.
- `src/components/ArcheologyMap.vue` — карта: кластеризация (`AttractionClusterIcon.vue` переиспользуется напрямую, не форкать), фото-пины с hover-карточкой, `YandexMapHint` на низком зуме.
- `src/components/ArcheologyControls.vue` — переключатель карта/каталог + поиск.

Дополнительно: `src/composables/useAttractions.js` (`extractGalleryAndCleanContent` — парсинг фото-галереи из HTML-контента через `DOMParser`), `src/composables/useHtmlContent.js` (`SITE_URL`, `decodeEntities`, `resolveRelativeUrls`), `src/composables/useFindings.js` (`useTaxonomyOptions(taxonomy)` — общая функция получения терминов таксономии, не завязана на findings, переиспользовать напрямую, не дублировать), `src/components/MultiSelectFilter.vue` (чекбокс-панель для таксономий с большим числом терминов), `src/components/CommentsSection.vue`.

После правок каждого `.vue`-файла — прогнать `node .claude/skills/impeccable/scripts/detect.mjs --json <файлы>` и в конце `npm run build`, как того требует `CLAUDE.md`.

---

## 1. Модуль «Природа» (`oopt-kirov` → `NatureView.vue`/`NatureItemView.vue`)

Это **полная замена** мока: реальные данные — точное соответствие `NatureView.vue`, вплоть до заголовка раздела (см. `api/docs/oopt-kirov.md`, §«Назначение»). Прочитать текущий мок (`src/views/NatureView.vue`, `src/views/NatureSiteView.vue`) — они задают тональность текста/подачи, которую нужно сохранить, но структуру карточек/фильтров привести к паттерну Археологии, а не оставлять текущую (в моке нет карты вовсе, хотя у объектов есть неиспользуемые пиксельные координаты — это будет заменено на настоящую Yandex-карту).

### 1.1 Важный нюанс контракта: источник данных для каталога

В отличие от Архитектуры/Археологии, `GET oopt/v1/objects` **не отдаёт таксономии** (`oopt_district`/`oopt_profile`/`oopt_species`) — по контракту они есть только в детальном ответе `/wp/v2/oopt/{id}?_embed=1` (см. `api/docs/oopt-kirov.md`). Так как в каталоге нужна фильтрация именно по этим таксономиям, а набор данных всего 17 записей — **не использовать `oopt/v1/objects` для каталога/карты Природы**. Вместо этого:

```
GET {API_BASE_URL}wp/v2/oopt?per_page=100&_embed=1
```

Один запрос отдаёт сразу всё нужное: `title.rendered`, зарегистрированные через `register_rest_field` мета-поля прямо в корне объекта (`oopt_coord`, `oopt_area`, `oopt_summary`, `oopt_imgSrc`, `oopt_color`), и таксономии в `_embedded['wp:term']` (плоский список термов на объект, отфильтровать по `t.taxonomy === 'oopt_district' | 'oopt_profile' | 'oopt_species'`). `oopt_species`-термы приходят с `meta.oopt_species_status` уже в этом же embed-контексте — бэкенд специально настроен так, чтобы не делать для этого отдельный запрос (см. `api/docs/oopt-kirov.md`, §«term meta с явным embed-контекстом»). При 17 записях это не тяжелее, чем облегчённый bulk-эндпоинт — сознательное отступление от общего правила «bulk без тяжёлых полей», а не ошибка; `oopt/v1/objects` остаётся рабочим эндпоинтом, просто не тем, что нужен здесь. Деталь объекта (`useOoptSite`) всё равно получать штатно через `GET wp/v2/oopt/{id}?_embed=1`, как в Археологии.

### 1.2 `useOopt.js`

По паттерну `useArcheology.js`:

- `natureCatalogState` — module-level `reactive` singleton: `{ district: 'all', profile: 'all', species: [], search: '', page: 1, perPage: 20, viewMode: 'map', scrollY: 0 }` (`species` — массив выбранных slug'ов, как `modelValue` у `MultiSelectFilter`).
- `useOoptSites()` → `{ items, loading, error, fetchItems }` — источник `wp/v2/oopt?per_page=100&_embed=1` (см. §1.1), нормализация: `coordinates` `[lat,lng]→[lng,lat]`, `districtTerms`/`profileTerms`/`speciesTerms` (с `status` из `term.meta.oopt_species_status`), `summarize` = `oopt_summary`, `imgSrc` = `oopt_imgSrc` с фоллбэком на `_embedded['wp:featuredmedia'][0].source_url`, `color` = `oopt_color`, `area` = `oopt_area`.
- `useOoptThumbnails()` — тот же батч-апгрейд миниатюр через `/wp/v2/media?include=...&_fields=id,media_details` с полной цепочкой фолбэка `medium_large → large → medium → full → миниатюра из bulk-ответа` (не сокращать цепочку — см. `CLAUDE.md`, зафиксированный баг на живых данных Архитектуры).
- `useOoptSite()` — `GET wp/v2/oopt/{id}?_embed=1`, `content` через `resolveRelativeUrls` + `extractGalleryAndCleanContent` (переиспользовать функцию из `useAttractions.js`, не копировать её тело — либо экспортировать её оттуда, либо, если такой экспорт неудобен, вынести в `useHtmlContent.js` и обновить импорт в `useAttractions.js` тоже, чтобы не остался дублирующийся код).
- `useOoptAdjacent()`, `useOoptNearby()` — обычные обёртки над `oopt/v1/objects/{id}/adjacent` и `.../nearby?max_distance=`.

### 1.3 Фильтры

- `oopt_district` (7 термов) и `oopt_profile` (3 терма) — иерархические, отображать через `FilterBar`, как era/type в Археологии. Опции **не хардкодить** (в отличие от статичных исторических эпох Археологии список районов/профилей — часть реального справочника, который может измениться) — получать их из уже загруженного `useOoptSites()`-каталога (собрать уникальные термы по всем 17 объектам) либо через `useTaxonomyOptions('oopt_district')`/`useTaxonomyOptions('oopt_profile')` из `useFindings.js`. Второй вариант проще и по паттерну совпадает с Этнографией — предпочесть его.
- `oopt_species` (28 термов, неиерархическая) — `MultiSelectFilter` (как material/origin в Этнографии), опции — `useTaxonomyOptions('oopt_species')`, `modelValue`/`update:modelValue` — `natureCatalogState.species`.
- Клиентская фильтрация поверх уже загруженного полного набора (17 записей, как в Археологии — не постранично): `district`/`profile` — точное совпадение slug, `species` — объект проходит, если пересечение его `speciesTerms` со `state.species` непусто (или `state.species` пуст).

### 1.4 Карта (`NatureMap.vue`, `NatureControls.vue`)

Копировать `ArcheologyMap.vue`/`ArcheologyControls.vue` почти без изменений: та же кластеризация (`AttractionClusterIcon.vue` напрямую), тот же паттерн фото-пина с hover-карточкой (заголовок + `place` = название района) и `YandexMapHint` на низком зуме, тот же floating/inline режим контролов внутри `#map`-слота `CatalogLayout`. Клик по пину/карточке — переход на `/nature/{id}`.

### 1.5 `NatureView.vue` / `NatureItemView.vue`

- `NatureView.vue` — по структуре `ArcheologyView.vue`: `CatalogLayout` со слотами `#heading/#description/#filters/#map/#grid/#pagination`, карточка — фото + теги (район, профиль) + заголовок + площадь (`{area} га`, если есть).
- `NatureItemView.vue` (переименовать/заменить `NatureSiteView.vue` — новое имя уже используется в документации бэкенда, см. `api/docs/oopt-kirov.md`, §«Замечания для интеграции») — по структуре `ArcheologyItemView.vue`: breadcrumb → intro (теги район/профиль, заголовок, площадь) → summary/content → **блок охраняемых видов** (чипы «Название (статус)», статус показывать только если `oopt_species_status` не пустой — готовый пример сборки такой строки на PHP-стороне см. `api/oopt-kirov/templates/single-oopt.php`, повторить ту же логику на клиенте) → prev/next → back-bar → nearby-сетка (по геодистанции, как у Археологии) → `CommentsSection` (`comment-status` из `raw.comment_status`).
- Обновить маршруты в `src/router/index.js`: `/nature/:slug` → `/nature/:id` (числовой WP ID, как у всех остальных реально-интегрированных модулей — `/archeology/:id` и т.д.), компонент `NatureSiteView.vue` → `NatureItemView.vue`.

---

## 2. Модуль «Лица Победы» (`geroi-vyatki` → `HeroesView.vue`/`HeroItemView.vue`) — полная замена мока

**Обновлено:** мока быть не должно вообще — весь `VictoryFacesView.vue`/`VictoryPersonView.vue` переделывается на реальные данные `hero`, ни один из пяти хардкоженных периодов/карточек не сохраняется. Это отменяет более раннюю идею «оставить таймлайн как есть, подменить одну карточку» — вместо неё полная замена, тем же приёмом, что и у Природы в разделе 1.

### 2.1 Честность контента: пересмотреть заявленный охват периодов

Реальные данные `hero` — это **только** Герои Советского Союза, уроженцы/связанные с Вятским краем, Великая Отечественная война (215 записей, см. `api/docs/geroi-vyatki.md`, §«Назначение»). Никаких данных по походу на Золотую Орду, обороне Хлынова, Первой мировой или локальным конфликтам новейшего времени в бэкенде нет и не появится — это были придуманные для мока категории.

Значит вместе с версткой меняется и текст `eyebrow`/`title`/`subtitle`/`#description` в `CatalogLayout`: убрать формулировки вида «с XIV века до наших дней», «от похода на Золотую Орду», «Первая мировая», «локальные конфликты новейшего времени» — они перестают быть правдой. Новый текст должен честно описывать именно то, что есть: Герои Советского Союза — уроженцы Вятского края, подвиги в Великой Отечественной войне 1941–1945, 215 имён. Заголовок раздела/маршрут (`Лица Победы`, `/victory`) можно оставить прежним (это по-прежнему уместное имя для раздела о героях войны), но подача — как в Архитектуре/Археологии: конкретный реальный каталог, а не иллюстративная композиция из вымышленных категорий.

Секцию CTA внизу страницы («Помогите сохранить память» — призыв присылать фото/письма для оцифровки) оставить: это не мок-данные, а осмысленный призыв к действию, применимый к любому набору реальных героев.

### 2.2 Маршруты

Модуль остаётся на тех же URL, меняется только параметр объекта (числовой WP ID вместо мокового `slug`, как у всех остальных реально-интегрированных модулей):

```js
{ path: '/victory',      name: 'victory',       component: () => import('@/views/HeroesView.vue') },
{ path: '/victory/:id',  name: 'victory-item',  component: () => import('@/views/HeroItemView.vue'), meta: { headerSolid: true } },
```

Переименовать файлы `src/views/VictoryFacesView.vue` → `src/views/HeroesView.vue` и `src/views/VictoryPersonView.vue` → `src/views/HeroItemView.vue` (привести к общему для проекта паттерну именования `{Module}View.vue`/`{Module}ItemView.vue`, как уже сделано для Природы, где `NatureSiteView.vue` переименован в `NatureItemView.vue`). Обновить оба пути в `src/router/index.js`.

Старые мок-импорты картинок, использовавшиеся только в этих двух файлах (`victory-hero.jpg` остаётся — это фон hero-секции каталога, не мок-данные; `victory-faces.jpg`, `kremlin.jpeg`(`trifonovImg`), `architecture-kirov.jpg`(`architectureImg`) — используются только как иллюстрации мок-карточек, других мест использования нет) — удалить вместе с мок-разметкой, которая их использовала.

### 2.3 `useHeroes.js`

Полная копия паттерна `useArcheology.js`, без специфики таксономийных фильтров (у `hero` она одна — `hero_status`, 2 терма):

- `heroesCatalogState` — module-level singleton `{ status: 'all', search: '', page: 1, perPage: 20, viewMode: 'map', scrollY: 0 }`.
- `useHeroes()` → источник `wp/v2/hero?per_page=100&_embed=1`, **не** `hero/v1/objects` — тот же приём, что и у Природы в §1.1: bulk-эндпоинт не отдаёт таксономию `hero_status`, а фильтр по ней нужен в каталоге; 215 записей — ещё разумный размер для одного embed-запроса без пагинации. `summarize` = `hero_feat_description` (в bulk-ответе `hero/v1/objects` это поле и так уже заполнено, но раз каталог не использует этот эндпоинт — читать `hero_feat_description` из корня объекта `wp/v2/hero`, он туда попадает через `register_rest_field`, см. `api/docs/geroi-vyatki.md`).
- `useHeroThumbnails()` — тот же батч-апгрейд миниатюр с полной цепочкой фолбэков (`medium_large → large → medium → full → миниатюра из bulk-ответа`, не сокращать). Важная оговорка для этого конкретного датасета: все 215 записей сейчас указывают на один и тот же тестовый плейсхолдер (см. `api/docs/geroi-vyatki.md`, §«Импорт») — визуально все карточки/пины будут с одинаковым фото, это не баг фронтенда и не повод городить фолбэк-логику под этот частный случай, само распределение фото по героям — забота бэкенда/будущего наполнения контента.
- `useHero()` — `GET wp/v2/hero/{id}?_embed=1`: `feat` = `hero_feat_description`, `awards` = `hero_awards`, `profession` = `hero_profession`, `connection` = `hero_connection` (обычный текст, **не** пытаться разбивать на чипы/теги — см. `api/docs/geroi-vyatki.md`, явно предупреждено), `birthDate`/`deathDate`/`birthPlace`/`deathPlace`, `status` из `_embedded['wp:term']` (`hero_status`), `content` через `resolveRelativeUrls` + `extractGalleryAndCleanContent`.
- `useHeroAdjacent()`, `useHeroNearby()` — обёртки над `hero/v1/objects/{id}/adjacent`/`.../nearby?max_distance=` (эти два кастомных эндпоинта по-прежнему используются как есть — они не нуждаются в таксономии).

### 2.4 Фильтры и карта

- Единственный фильтр — `hero_status` («Историческая»/«Современная») через `FilterBar`, 2 опции, задать статично (ровно 2 фиксированных терма таксономии).
- `HeroMap.vue`/`HeroControls.vue` — копия `ArcheologyMap.vue`/`ArcheologyControls.vue`: кластеризация (`AttractionClusterIcon.vue` напрямую), фото-пины с hover-карточкой (`place` = место рождения), `YandexMapHint` на низком зуме, floating/inline режим контролов.

### 2.5 `HeroesView.vue` / `HeroItemView.vue`

- `HeroesView.vue` — структура `ArcheologyView.vue`: `CatalogLayout` со слотами `#heading/#description/#filters/#map/#grid/#pagination` (текст — см. §2.1), карточка — фото + тег статуса + ФИО + место рождения + `summarize` (краткое описание подвига, обрезать по CSS `line-clamp`, как принято для длинных описаний в карточках других модулей).
- `HeroItemView.vue` — структура `ArcheologyItemView.vue`:
  - breadcrumb → intro: тег статуса, ФИО, место/даты рождения и смерти (`hero_death_date` может быть пустым — герой либо погиб в неизвестную дату, либо жив; не выводить пустую строку, скрывать блок даты смерти целиком, если `deathDate` пуст).
  - summary/content: `hero_feat_description` как ключевая аннотация (аналог `summarize` в Археологии), затем `hero_awards`, `hero_profession`, `hero_connection` — как отдельные подписанные блоки (не единый текст, поля семантически разные), затем полный `content` (биография) с галереей из текста, если есть.
  - prev/next, back-bar, nearby-сетка по геодистанции (`max_distance` — переиспользовать существующую `window.__ATTRACTION_NEARBY_RADIUS_KM__`, радиус там не специфичен для одного модуля, отдельную переменную под героев не заводить), `CommentsSection` (`comment-status` из `raw.comment_status`).

---

## 3. Общая проверка

- `php -l` не требуется (бэкенд не меняется).
- `npm run build` — без ошибок.
- `node .claude/skills/impeccable/scripts/detect.mjs --json <изменённые .vue>` — прогнать по каждому изменённому/новому `.vue`-файлу.
- Живая проверка (Docker или `testsite.vyatgeo.ru`, если доступен): `/nature` и `/victory` — карта отрисовывается, кластеры/пины кликабельны, фильтры (`district`/`profile`/`species` у Природы, `hero_status` у Героев) реально сужают выдачу и на карте, и в каталоге; переход на объект и обратно сохраняет фильтр/скролл (module-level singleton); ни на `/victory`, ни на `/victory/:id` не осталось мок-текста/мок-карточек/неиспользуемых мок-импортов картинок; комментарии отправляются и подгружаются на страницах объектов.
- Проверить, что нигде в проекте не осталось внешних ссылок на старые файлы/маршруты (`VictoryFacesView.vue`, `VictoryPersonView.vue`, `/victory/:slug`) — например, в `SiteHeader.vue`/меню, редиректе классических пермалинков (`nv_spa_resolve_wp_permalink_redirect` в `functions.php` — если там было исключение под `hero`/`victory`, актуализировать) или других `.vue`-файлах.
