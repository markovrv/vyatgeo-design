# Промпт для ИИ-агента: разработка двух WordPress-плагинов по образцу «attraction»

## 0. Контекст и задача

В проекте уже есть эталонный плагин **«Архитектура и скульптура г. Кирова»**: `attraction.php`, `activation.php`, `register-attraction.php`, `rest-api.php`, `templates/single-attraction.php`, `templates/template_for_yamap.php`, `js/adminMap.js`, `vue/index.js` + `vue/index.css` (собранный Vue3-бандл, ~380–550 КБ, на базе `vuestic-ui`). Файлы прочитаны и разобраны полностью.

Нужно воспроизвести архитектуру для двух новых плагинов на основе `Geroi.xlsx`:

- **Лист1** → плагин **«Герои Вятского края»** (тип записи `hero`)
- **Лист2** → плагин **«Памятники природы»** (тип записи `oopt`)

### 0.1. Важное уточнение: фронтенд и REST-контракт

**Исходник фронтенда доступен** — это отдельный Vue 3 + Vite проект по пути `C:\Users\Роман\Desktop\Проекты\wp\attraction\vue\attraction-map` (на базе `vuestic-ui` + `vue-yandex-maps`, собирается через `vite build` в `vue/index.js` + `vue/index.css`). Его код полностью разобран — см. ниже, как это влияет на требования.

**Ключевое решение по архитектуре:** старый standalone-бандл (`vue/index.js` + `vue/index.css`) и страница-обёртка `template_for_yamap.php` **не копируются** в новые плагины. Вместо этого фронтенд будет реализован в рамках текущего Vue 3 + Vite SPA-проекта (`naslediye-vyatki/src/`), который уже обслуживает разделы «Архитектура», «Этнография» и «Города» через единый headless-подход:

- SPA-тема WordPress (`wp-theme/naslediye-vyatki-theme/functions.php`) перехватывает все фронтовые URL и отдаёт собранный Vue-бандл.
- Композаблы (`src/composables/use*.js`) обращаются к WP REST API — как к стандартным эндпоинтам (`/wp/v2/{cpt}/{id}`), так и к кастомным (`/{module}/v1/objects`, `/{module}/v1/objects/{id}/adjacent`, `/{module}/v1/objects/{id}/nearby`).
- Страницы каталога/объекта внутри SPA — `src/views/{Module}View.vue` и `src/views/{Module}ItemView.vue`.

**Что это меняет в требованиях к плагинам:**

| Старое требование (образец) | Новое требование |
|---|---|
| Скопировать `vue/index.js` + `vue/index.css` из образца | **Не нужно.** Фронтенд делается в SPA-проекте отдельно. |
| Создать `templates/template_for_yamap.php` + страницу-обёртку через `register_activation_hook` | **Не нужно.** Standalone-карта не создаётся — карта/каталог будут внутри SPA. |
| Проверять зависимость от плагина Yamaps в `activation.php` | **Опционально.** Нужна только если в `single-{slug}.php` используются шорткоды `[yamap]`/`[yaplacemark]`. |
| `/objects` должен строго совпадать с форматом, ожидаемым старым бандлом | `/objects` должен соответствовать контракту, ожидаемому SPA-композаблами — **формат тот же** (см. ниже), просто потребитель другой. |

**Контракт `/objects`, ожидаемый SPA-композаблами** (на примере `useAttractions.js` — остальные модули с картой будут повторять этот же паттерн):

```json
{
  "id": 123,
  "coordinates": [58.6, 49.6],
  "title": "…",
  "place": "…",
  "imgSrc": "…",
  "color": "…",
  "summarize": "…",
  "content": "",
  "img": 456
}
```

Это **ровно та же структура**, что и у образца `attraction`. `coordinates` — массив `[lat, lng]` (SPA меняет порядок на `[lng, lat]` для ymaps3). `content` всегда пустая строка в bulk-ответе. `img` — ID вложения для батч-апгрейда миниатюр через `/wp/v2/media?include=id1,id2,...`. Полная доменная информация доступна через `/wp/v2/{cpt}/{id}?_embed=1`.

**Контракт `/objects/{id}/adjacent`** (используется SPA для навигации «Предыдущий/Следующий» на странице объекта):

```json
{ "prev": { "id": 122, "title": "…", "place": "…", "imgSrc": "…", "img": 455 } | null,
  "next": { "id": 124, "title": "…", "place": "…", "imgSrc": "…", "img": 457 } | null }
```

**Контракт `/objects/{id}/nearby?max_distance=N`** (используется SPA для блока «Объекты рядом»):

```json
{ "nearby": [ { "id": 125, "title": "…", "place": "…", "imgSrc": "…", "img": 458, "distanceKm": 0.532 }, … ] }
```

`max_distance` — **обязательный** query-параметр, значение передаётся с клиента (`window.__ATTRACTION_NEARBY_RADIUS_KM__` или аналогичная переменная из Customizer). До 4 ближайших, сортировка по расстоянию, `distanceKm` с 3 знаками после запятой.

Кроме того, CPT должен быть зарегистрирован с `show_in_rest => true`, а все мета-поля — через `register_rest_field` (с `get_callback`/`update_callback` через `get_post_meta`/`update_post_meta`). SPA получает детальную информацию об объекте штатным запросом `GET /wp/v2/{cpt}/{id}?_embed=1`.

**Резюме:** REST API остаётся тем же по структуре, что и в образце, но потребитель — не старый standalone-бандл, а SPA-композаблы. Файлы `vue/`, `template_for_yamap.php` и механика standalone-карты (активация страницы-обёртки, `window.map.*`) — **не создаются**.

### 0.2. Структура файлов плагина (обновлённая)

Каждый плагин состоит из следующих файлов:

1. **`{slug}.php`** — главный файл: шапка `Plugin Name`, фильтр `template_include` (только шаблон одиночной записи — `single-{slug}.php`; шаблон SPA-карты больше не нужен), `include`-ы `register-{slug}.php` и `rest-api.php`. `activation.php` включается только если нужна проверка Yamaps для `single-{slug}.php`.
2. **`register-{slug}.php`** — `register_post_type`, `register_taxonomy`, `register_rest_field` для каждого мета-поля, `use_block_editor_for_post_type` → `false`, `add_meta_box`, `save_post`.
3. **`rest-api.php`** — кастомные REST-роуты: `/objects`, `/objects/{id}/adjacent`, `/objects/{id}/nearby`.
4. **`templates/single-{slug}.php`** — шаблон одиночной записи для WordPress-рендеринга (вне SPA).
5. **`js/adminMap.js`** — копия из образца, меняется только ID инпута координат.
6. **Скрипт импорта** — однократный перенос данных из `Geroi.xlsx`.

---

## 1. Плагин «Герои Вятского края»

**Slug:** `geroi-vyatki` · **Post type:** `hero` · **Метки:** «Герой» / «Герои» (по аналогии с `labels` у `attraction`, тот же набор ключей: `add_new`, `add_new_item`, `edit_item`, `view_item`, `search_items`, `not_found`, `not_found_in_trash`, `parent`).

`register_post_type` параметры — как у `attraction`: `public => true`, `show_in_rest => true`, `supports => ['title','editor','comments','revisions','thumbnail','post-thumbnails']`, `has_archive => true`, свой `menu_icon` (например `dashicons-awards`).

### 1.1. Сопоставление колонок Excel → поля WP

| Колонка Excel (Лист1) | Поле WP | Тип |
|---|---|---|
| ФИО | `post_title` | стандартное поле |
| Литературная Биография | `post_content` (визуальный редактор) | стандартное поле |
| Фото | featured image (`post-thumbnail`) | стандартное поле |
| Дата рождения | meta `hero_birth_date` | text (хранить как строку, формат `дд.мм.гггг`; часть значений в источнике — не полные даты) |
| Дата смерти (если есть)/если жив NULL | meta `hero_death_date` | text, допускается пустая строка / `NULL` как признак «жив» |
| Место рождения | meta `hero_birth_place` | text |
| Место смерти (если есть) | meta `hero_death_place` | text, допускается пусто |
| Описание подвига, заслуг, вклада | meta `hero_feat_description` | textarea (аннотация; это же значение отдаётся в `/objects` как `summarize`) |
| Перечень наград (ордена, медали, звания) | meta `hero_awards` | textarea |
| Профессия, деятельность в мирное время | meta `hero_profession` | text |
| Характер связи с Вятским краем («родился», «жил», «работал», «совершил подвиг», «похоронен» и т.п.) | meta `hero_connection` | textarea — значение переносится как есть, без парсинга на отдельные факты (было таксономией, отказались: в источнике это не короткие метки, а россыпь предложений произвольной длины, и деление по `,`/`.` неизбежно даёт либо склеенные, либо разорванные посреди сокращений («пос.», «г.») факты) |
| Координаты места в Вятском крае | meta `hero_coord` | text `"lat, lng"` — по аналогии с `attraction_coord` |
| *(нет прямого столбца)* | meta `hero_imgSrc` | text URL — ручной override картинки для маркера на карте (label в админке «Url картинки для карты»); если пусто — фоллбэк на featured image |
| *(нет прямого столбца)* | meta `hero_color` | color picker — цвет маркера на карте. По умолчанию проставлять при импорте по значению таксономии `hero_status` (например, «историческая» — один цвет, «современная» — другой); поле остаётся редактируемым вручную |

### 1.2. Таксономии

| Колонка Excel | Таксономия | Тип | Термины |
|---|---|---|---|
| Статус персоналии («историческая», «современная») | `hero_status` | иерархическая (как категории), ровно 2 термина | «Историческая», «Современная» |

Единственная таксономия у `hero` — `hero_status`, регистрировать с `show_in_rest => true`, чтобы термин был доступен в стандартном REST-ответе `/wp/v2/hero`. «Характер связи с Вятским краем» — meta-поле, не таксономия (см. §1.1).

### 1.3. Админ-панель

Два `add_meta_box` (по аналогии с `attraction_summarize` и `attraction_map_box`):

- **«Подвиг и биография»** — поля `hero_feat_description` (textarea), `hero_awards` (textarea), `hero_profession` (input text), `hero_connection` (textarea).
- **«Место и координаты»** — карта Yandex Maps (переиспользовать `adminMap.js` из `attraction`, тот же паттерн `id="map"` + `id="attraction_coord-input"` → переименовать в `hero_coord-input`), input `hero_coord`, input `hero_birth_place`, input `hero_death_place`, input `hero_birth_date`, input `hero_death_date`, input text `hero_imgSrc` («Url картинки для карты»), input `type="color"` `hero_color` («Цвет маркера для карты»).

`save_post`-обработчик — по образцу `register-attraction.php`: для каждого поля `isset($_POST['...']) && $_POST['...'] != ''` → `update_post_meta`, иначе — очистка meta пустой строкой.

### 1.4. REST API (`rest-api.php`, неймспейс `hero/v1`)

- **`GET hero/v1/objects`** — все опубликованные `hero`, функция `get_hero_objects()`, структурно копирует `get_attraction_objects()` (тот же `WP_Query` + `array_reverse` в конце), маппинг доменных полей в обобщённый контракт (см. §0.1):

  | Ключ в JSON `/objects` | Источник |
  |---|---|
  | `id` | `$post_id` |
  | `coordinates` | `explode(', ', $meta['hero_coord'][0] ?? '0, 0')` → `[lat, lng]` |
  | `title` | `get_the_title()` (ФИО) |
  | `place` | `$meta['hero_birth_place'][0] ?? ''` (место рождения — как основная географическая привязка) |
  | `imgSrc` | `$meta['hero_imgSrc'][0] ?? ''`, фоллбэк на `wp_get_attachment_image_url($thumbnail_id, 'thumbnail')` |
  | `color` | `$meta['hero_color'][0] ?? ''` |
  | `summarize` | `$meta['hero_feat_description'][0] ?? ''` |
  | `content` | `''` (всегда пусто в bulk-ответе) |
  | `img` | `$thumbnail_id` |

  Доменные поля (даты, награды, профессия, характер связи, таксономия статуса) в этот эндпоинт **не включаются** — они доступны через `/wp/v2/hero/{id}`.

- `GET hero/v1/objects/{id}/adjacent` — предыдущий/следующий по `ID`, без фильтра. Ответ: `{ "prev": navItem | null, "next": navItem | null }`, где `navItem` = `{ id, title, place, imgSrc, img }`.
- `GET hero/v1/objects/{id}/nearby?max_distance=N` — гаверсинус по `hero_coord`, `max_distance` обязателен, до 4 ближайших, сортировка по расстоянию. Ответ: `{ "nearby": [ { ...navItem, distanceKm }, ... ] }`.
- В `register_rest_field` (для `/wp/v2/hero`) зарегистрировать все мета-поля: `hero_birth_date`, `hero_death_date`, `hero_birth_place`, `hero_death_place`, `hero_feat_description`, `hero_awards`, `hero_profession`, `hero_connection`, `hero_coord`, `hero_imgSrc`, `hero_color` — с `get_callback`/`update_callback` через `get_post_meta`/`update_post_meta`.

---

## 2. Плагин «Памятники природы»

**Slug:** `oopt-kirov` · **Post type:** `oopt` · **Метки:** «Памятник природы» / «Памятники природы».

`register_post_type` — аналогично: `show_in_rest => true`, `supports => ['title','editor','thumbnail','post-thumbnails','comments','revisions']`, `has_archive => true`, `menu_icon` (например `dashicons-palmtree`).

### 2.1. Сопоставление колонок Excel → поля WP

| Колонка Excel (Лист2) | Поле WP | Тип |
|---|---|---|
| Название ООПТ | `post_title` | стандартное поле |
| Литературное описание | `post_content` | стандартное поле |
| Фото | featured image | стандартное поле |
| Координаты | meta `oopt_coord` | text `"lat, lng"` |
| Площадь, га | meta `oopt_area` | number (float) |
| Охраняемые виды | таксономия `oopt_species` (см. §2.2) | — |
| Краткое описание | meta `oopt_summary` | textarea (аннотация; это же значение отдаётся в `/objects` как `summarize`) |
| *(нет прямого столбца)* | meta `oopt_imgSrc` | text URL — override картинки для маркера; фоллбэк на featured image |
| *(нет прямого столбца)* | meta `oopt_color` | color picker — цвет маркера на карте. По умолчанию проставлять по значению таксономии `oopt_profile` (единый цвет на профиль памятника природы); поле редактируемо вручную |

### 2.2. Таксономии

| Колонка Excel | Таксономия | Тип | Примечание |
|---|---|---|---|
| Район | `oopt_district` | иерархическая (категории) | список районов извлекается из уникальных значений столбца при импорте |
| Профиль | `oopt_profile` | иерархическая (категории) | значения из источника: «Гидрологический», «Ботанический», «Геологический» и др. — полный список выгрузить скриптом импорта, не хардкодить |
| Охраняемые виды | `oopt_species` | неиерархическая (как метки), множественный выбор | значения парсятся из ячейки по разделителю `,` |

Все три — `show_in_rest => true`.

**`oopt_species` — термин без охранного статуса в скобках.** В источнике ячейка выглядит как `Кувшинка чисто-белая (Приложение КККО), серая цапля (III КККО)`. При парсинге каждого элемента списка:

- название термина — часть строки **до** открывающей скобки, `trim()`;
- содержимое в скобках (охранный статус/примечание — `КККО`, `Приложение 2 КККО`, `КК РФ`, `МСОП`, `немногочисленный гнездящийся` и т.п.) **не входит в название термина** — сохраняется как **term meta** этого термина, ключ `oopt_species_status`, через `register_term_meta('oopt_species', 'oopt_species_status', [...])` с `single => true`, `show_in_rest => true`, `type => 'string'`;
- если скобок нет (значение вида «Обыкновенный гоголь» без пометки) — term meta остаётся пустой строкой;
- один и тот же вид в источнике почти всегда повторяется с одинаковым примечанием в разных строках — термин ищется по названию (`get_term_by('name', ...)`) и переиспользуется, а не создаётся заново; `oopt_species_status` при повторном использовании термина не перезаписывается, если уже заполнена.

### 2.3. Админ-панель

Два `add_meta_box`:

- **«Аннотация»** — `oopt_summary` (textarea).
- **«Карта и параметры»** — карта Yandex (тот же `adminMap.js`, `id` инпута `oopt_coord-input`), input `oopt_coord`, input number `oopt_area`, input text `oopt_imgSrc` («Url картинки для карты»), input `type="color"` `oopt_color` («Цвет маркера для карты»).

`oopt_species` в редактор записи отдельный meta-box не выносится — как у любой неиерархической таксономии с `show_in_rest => true` и без `meta_box_cb => false`, WP сам добавляет стандартный tags-бокс («Охраняемые виды») с автодополнением по уже существующим терминам. Поле `oopt_species_status` (охранный статус) редактируется не на странице записи, а на экране самого термина (`edit-tags.php?taxonomy=oopt_species`) — через `oopt_species_add_form_fields`/`oopt_species_edit_form_fields`, обычный текстовый input, без карты.

`save_post` — та же схема условного сохранения/очистки, что и в `register-attraction.php`.

### 2.4. REST API (`rest-api.php`, неймспейс `oopt/v1`)

- **`GET oopt/v1/objects`** — все опубликованные `oopt`, функция `get_oopt_objects()`, структурно копирует `get_attraction_objects()`, маппинг в обобщённый контракт (см. §0.1):

  | Ключ в JSON `/objects` | Источник |
  |---|---|
  | `id` | `$post_id` |
  | `coordinates` | `explode(', ', $meta['oopt_coord'][0] ?? '0, 0')` → `[lat, lng]` |
  | `title` | `get_the_title()` (название памятника природы) |
  | `place` | название термина таксономии `oopt_district` (район) — через `get_the_terms($post_id, 'oopt_district')`, т.к. отдельного текстового поля «место» в источнике нет |
  | `imgSrc` | `$meta['oopt_imgSrc'][0] ?? ''`, фоллбэк на thumbnail |
  | `color` | `$meta['oopt_color'][0] ?? ''` |
  | `summarize` | `$meta['oopt_summary'][0] ?? ''` |
  | `content` | `''` (всегда пусто в bulk-ответе) |
  | `img` | `$thumbnail_id` |

  Площадь, охраняемые виды, профиль и т.д. — только в `/wp/v2/oopt/{id}?_embed=1` (площадь/аннотация — через `register_rest_field`, охраняемые виды и профиль — таксономии, приходят в `_embedded['wp:term']`), в `/objects` не выводятся.

- `GET oopt/v1/objects/{id}/adjacent` — по возрастанию `ID`. Ответ: `{ "prev": navItem | null, "next": navItem | null }`.
- `GET oopt/v1/objects/{id}/nearby?max_distance=N` — гаверсинус по `oopt_coord`, обязательный `max_distance`, до 4 ближайших. Ответ: `{ "nearby": [ { ...navItem, distanceKm }, ... ] }`.
- `register_rest_field` для `/wp/v2/oopt`: `oopt_coord`, `oopt_area`, `oopt_summary`, `oopt_imgSrc`, `oopt_color` (`oopt_species` больше не в этом списке — это таксономия, а не meta-поле).
- `register_term_meta('oopt_species', 'oopt_species_status', [...])` с `show_in_rest => true` — чтобы охранный статус вида приходил вместе с термином в `_embedded['wp:term']` без отдельного запроса к `/wp/v2/oopt_species/{term_id}`.

---

## 3. Общие требования к обоим плагинам

### 3.1. Структура и независимость

- Каждый плагин — независимая папка/zip, устанавливаемая отдельно, без общего кода (полное копирование паттерна `attraction`, а не рефакторинг в общую библиотеку).
- Главный файл `{slug}.php` содержит шапку `Plugin Name`, фильтр `template_include` (priority `1`) только для `single-{slug}.php`, и `include`-ы `register-{slug}.php` и `rest-api.php`.

### 3.2. Что НЕ создаётся (отличия от образца)

- **Папка `vue/`** — не создаётся. Старый standalone-бандл (`index.js` + `index.css`) не копируется. Фронтенд будет реализован в SPA-проекте отдельно.
- **`templates/template_for_yamap.php`** — не создаётся. Standalone-страница карты не нужна (карта/каталог будут внутри SPA).
- **`activation.php`** — не создаётся (нет нужды в активации standalone-страницы карты). Если в `single-{slug}.php` используются шорткоды `[yamap]`/`[yaplacemark]` — добавить `activation.php` только с проверкой Yamaps через `admin_notices`.
- **SPA-страница карты через `wp_insert_post`** — не создаётся.

### 3.3. REST API

- Все кастомные эндпоинты регистрируются с `permission_callback => '__return_true'`.
- **`GET {slug}/v1/objects`** — без пагинации (`posts_per_page => -1`), все опубликованные записи, `array_reverse` в конце. Ответ — массив объектов в обобщённом формате `{ id, coordinates: [lat, lng], title, place, imgSrc, color, summarize, content: "", img }`. `summarize` — короткая аннотация из соответствующего meta-поля (`hero_feat_description`/`oopt_summary`, см. §1.4/§2.4), отдаётся сразу в bulk-ответе. `content` — единственное тяжёлое поле, всегда пустая строка в bulk-ответе; полный текст — через `/wp/v2/{cpt}/{id}?_embed=1`.
- **`GET {slug}/v1/objects/{id}/adjacent`** — `{ prev: navItem | null, next: navItem | null }`, сортировка по возрастанию ID.
- **`GET {slug}/v1/objects/{id}/nearby?max_distance=N`** — геопоиск по формуле гаверсинуса, `max_distance` обязателен (значение передаётся с клиента, не хардкодится на сервере), до 4 ближайших, `{ nearby: [ { ...navItem, distanceKm }, ... ] }`.
- **`register_rest_field`** для всех доменных мета-полей — чтобы они были доступны в стандартном REST `/wp/v2/{cpt}/{id}?_embed=1`. Обязательные поля: координаты, `imgSrc`, `color` + все доменные поля из §1.1/§2.1.
- **`show_in_rest => true`** для CPT и всех таксономий.
- `coordinates` в `/objects` — всегда массив `[lat, lng]` (SPA меняет порядок на `[lng, lat]` при использовании). Если координаты отсутствуют — `[0, 0]` (как в образце).
- **`save_post` обязан выходить раньше своей логики при `defined('REST_REQUEST') && REST_REQUEST`.** При запросах через REST (SPA, скрипты импорта) `$_POST` всегда пуст; если хук по образцу `attraction` безусловно очищает meta-поле, не найдя ключ в `$_POST` (`else { update_post_meta($id, 'field', ''); }`), то любое REST-обновление поста — даже не трогающее эти поля вовсе — стирает их все до пустой строки. Тот же guard — в аналогичных `created_{taxonomy}`/`edited_{taxonomy}` хуках term-meta, если у них есть деструктивная `else`-ветка с `delete_term_meta` (без такой ветки хук не деструктивен и в guard'е не нуждается).
- **`update_callback` в `register_rest_field` не должен быть `if (!$value) return;`/`return 0;`.** Такая охрана не даёт **очистить** поле через REST — `update_callback` и так вызывается WP только когда ключ реально присутствует в теле запроса, поэтому явная пустая строка — осознанный запрос на очистку. Верная форма: `return update_post_meta($object->ID, $field_name, $value ?? '');` (для term-meta — `$object->term_id`, не `$object->ID`, которого у `WP_Term` не существует). Клиент, желающий очистить поле, обязан передавать `''`, не `null` — `null` в JSON-теле WP-ядро (`isset()`) считает отсутствующим ключом и `update_callback` не вызывает вовсе.
- **`register_term_meta` для term-meta, которое должно приходить в `_embedded['wp:term']` при `?_embed=1`**, — недостаточно `'show_in_rest' => true`: короткая форма добавляет поле только в контексты `view`/`edit`, но не `embed`. Нужно явно: `'show_in_rest' => ['schema' => ['type' => 'string', 'context' => ['view', 'edit', 'embed']]]` — иначе клиенту придётся делать отдельный запрос к `/wp/v2/{taxonomy}/{term_id}` на каждый термин вместо получения значения одним запросом вместе с постом.

### 3.4. Админ-панель и `adminMap.js`

- Числовые/координатные поля всегда хранить как строку `"lat, lng"` в одном meta-поле (как `attraction_coord`), чтобы переиспользовать существующий `adminMap.js` без переписывания.
- `js/adminMap.js` — **копируется из образца практически без изменений**, меняется только ID инпута (`attraction_coord-input` → `{slug}_coord-input`). Карта в админке использует классический Yandex Maps JS API v2.1 (`ymaps.Map`/`ymaps.GeoObject`), а не `vue-yandex-maps`/ymaps3.
- Все тексты меток/подписей — на русском, в стиле существующего плагина.

### 3.5. `single-{slug}.php`

Шаблон одиночной записи для прямого WordPress-рендеринга (используется, когда запись открывается вне SPA — например, предпросмотр из админки или прямой переход по URL записи).

- По образцу `single-attraction.php`: вывод заголовка, контента, мини-карты через шорткоды `[yamap]`/`[yaplacemark]` (если плагин Yamaps активен), кнопка-ссылка «Вернуться к каталогу».
- `center`/`coord` карты = значение `{slug}_coord`, `name` = аналог поля `place` (`hero_birth_place` для героев, название района для памятников природы).
- Если Yamaps не используется — вывести координаты текстом и/или статическую карту через Yandex Static API (без зависимости от плагина).

### 3.6. Скрипт импорта

Однократный перенос всех строк `Geroi.xlsx` (223 строки на Листе1, 18 строк на Листе2) в соответствующие посты:

- `wp_insert_post` с `post_title`, `post_content`, `post_status => 'publish'`, `post_type` → `hero`/`oopt`.
- `update_post_meta` для каждого мета-поля из §1.1/§2.1.
- `wp_set_object_terms` для таксономий (`hero_status`, `oopt_district`, `oopt_profile`, `oopt_species`). Термины создаются автоматически через `wp_set_object_terms` (если термина нет — он будет создан для иерархических таксономий через `wp_insert_term` при необходимости). `hero_connection` — meta-поле, не таксономия, переносится через `update_post_meta` вместе с остальными доменными полями.
- **Парсинг «Охраняемые виды» → `oopt_species`**: split ячейки по `,`, для каждого элемента — `preg_match('/^(.*?)\s*\(([^)]+)\)\s*$/u', ...)`; если совпало — группа 1 (после `trim`) идёт названием термина, группа 2 — в `oopt_species_status`; если скобок нет — весь элемент целиком идёт названием термина, `oopt_species_status` не заполняется. Термин ищется по имени перед созданием (`get_term_by('name', $name, 'oopt_species')`), чтобы повторяющиеся виды из разных строк схлопывались в один термин, а не дублировались. Значение вида «Редких видов не выявлено» (используется как признак отсутствия охраняемых видов, а не как название вида) — не создавать термином, при импорте пропускать.
- Загрузка фото по URL/файлу в медиатеку (`media_handle_sideload` или `wp_insert_attachment`) и назначение `set_post_thumbnail`.
- Значения `hero_color`/`oopt_color` проставлять программно по таксономии: например, `hero_status = 'Историческая'` → один цвет, `'Современная'` → другой; `oopt_profile = 'Гидрологический'` → синий, `'Ботанический'` → зелёный и т.д. — полный маппинг цветов определить на этапе реализации.
- Формат реализации: PHP-скрипт, запускаемый через WP-CLI или как админ-страница (по аналогии с `admin/import.php` из темы).

### 3.7. Проверка результата

После реализации каждого плагина выполнить:

```bash
# Синтаксис PHP
php -l {slug}.php && php -l register-{slug}.php && php -l rest-api.php

# Проверка эндпоинтов (после установки на WP)
curl -s {site}/wp-json/{slug}/v1/objects | jq 'length'
curl -s {site}/wp-json/{slug}/v1/objects | jq '.[0] | keys'
curl -s "{site}/wp-json/wp/v2/{cpt}/1?_embed=1" | jq '.title.rendered, .{slug}_coord, ._embedded["wp:featuredmedia"][0].source_url'
```
