# Плагин «Этнографическая коллекция археологической лаборатории» (artifact-finder)

Файлы: `api/artifact-finder/` — `artifact-finder.php`, `activation.php`, `register-findings.php`, `rest-api.php`, `included/css|js`.

## Назначение

Каталог экспонатов этнографической коллекции лаборатории ВятГУ. Плагин полностью серверный — своего Vue/SPA-бандла не содержит, весь рендеринг (архив и карточка находки) идёт через фильтры темы Blocksy (`the_content`, `blocksy:posts-listing:cards:custom-output`, `template_include`). REST API существует отдельно как самостоятельный слой для внешних потребителей (в частности — для будущей интеграции с `naslediye-vyatki`).

## Регистрируемые сущности

- **CPT `finding`** (`register-findings.php:4`) — `show_in_rest: true`, `supports: title, editor, thumbnail`, архив включён.
- **Таксономии** (все `hierarchical: true`, `show_in_rest: true`):
  - `finding_material` — материал
  - `finding_origin` — происхождение
  - `finding_receipt_time` — время поступления
  - `finding_creation_time` — время создания
- **Мета-поля**, добавленные в REST через `register_rest_field` (`register-findings.php:100-160`):
  - `finding_dimensions` — размеры (строка «ш×д×в»)
  - `finding_cat_id` — номер по каталогу
  - `finding_functionality` — функционал (текст)
  - `finding_features` — особенности (текст)
  - `finding_additional_images` — массив ID вложений (галерея; первое изображение автоматически становится featured image при сохранении, `artifact-finder.php:414-451`)

Gutenberg для `finding` отключён — редактирование только через классические метабоксы с drag-and-drop сортировкой изображений (`register-findings.php:218-411`).

## REST API

### Стандартный WP REST
`GET/POST /wp/v2/finding` — стандартный CRUD с уже подмешанными выше мета-полями (доступны на чтение и запись через штатный endpoint тоже, не только через кастомный).

### Кастомные роуты (`rest-api.php`)

| Метод | Роут | Доступ | Назначение |
|---|---|---|---|
| GET | `/findings/v1/findings/` | публичный (`__return_true`) | Список находок с фильтрацией и пагинацией |
| POST | `/findings/v1/findings/{id}` | `edit_posts` (авторизация обязательна) | Массовое обновление таксономий + мета-полей одной находки |

**GET `/findings/v1/findings/`** — параметры query: `per_page` (по умолч. 12), `page`, `search` (поиск по заголовку), `finding_material`, `finding_origin`, `finding_creation_time` (slug'и таксономий, при >1 таксономии — `relation: AND`). Ответ:

```json
{
  "findings": [ { "id": 20622, "title": "...", "content": "...", "thumbnail": "...", "dimensions": "...", "cat_id": "...", "materials": ["..."], "origin": ["..."], "creation_time": ["..."], "receipt_time": ["..."], "additional_images": ["url1", "url2"] } ],
  "total": 123, "total_pages": 11, "current_page": 1
}
```
Проверено на живом сервере (`per_page=1`) — endpoint отвечает 200 и отдаёт реальные записи коллекции.

**POST `/findings/v1/findings/{id}`** — тело запроса строго типизировано в два блока:
```json
{
  "taxonomies": { "finding_material": [12, 15] },
  "meta_fields": { "finding_dimensions": "10x5x3", "finding_additional_images": [101, 102] }
}
```
Разрешённый список `meta_fields` жёстко задан в коде (`rest-api.php:178-184`); попытка обновить произвольное поле вернёт `meta_errors`. ID терминов и метаполе `finding_additional_images` валидируются построчно, ошибки собираются в ответ (`taxonomy_errors`/`meta_errors`), а не прерывают весь запрос — частичный успех возможен.

## CORS

Явного CORS-заголовка плагин не выставляет. Проверка живого сервера с `Origin: http://localhost:5173` показала, что WordPress-ядро само отражает Origin запроса (`Access-Control-Allow-Origin: <origin запроса>`, `Access-Control-Allow-Credentials: true`) для всех `/wp-json/*` маршрутов — межпоточные запросы из Vue SPA работают без дополнительной настройки.

## Замечания для интеграции

- Единственный из 4 плагинов, где есть **write-эндпоинт** — но он требует `edit_posts`, т.е. предназначен для авторизованного административного клиента (cookie-based или Application Password), а не для публичного фронтенда naslediye-vyatki.
- Для каталога/фильтров на фронтенде проще и правильнее использовать кастомный `GET /findings/v1/findings/` — он уже отдаёт денормализованные названия таксономий и URL изображений, в отличие от штатного `/wp/v2/finding`, где термины пришлось бы резолвить отдельными запросами.
- `dimensions`, `cat_id` и т.п. в списке — сырые строки без единиц измерения в JSON (единицы «см» подразумеваются по контексту UI, как видно из `finding_dimensions` label «Размеры (ш×д×в) см»).
