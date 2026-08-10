# Плагин «Памятники археологии Кировской области» (archeology)

Файлы: `api/archeology/` — `register-archeology.php`, `rest-api.php`, `js/adminMap.js`.

## Назначение

Каталог памятников археологии Кировской области с координатами для карты.
Структура — точная копия модуля «Архитектура» (`attraction`), с добавлением
таксономий `archeology_era` (эпоха) и `archeology_type` (тип памятника).

## Регистрируемые сущности

- **CPT `archeology`** — `show_in_rest: true`, `supports: title, editor, comments, revisions, thumbnail`.
- **Таксономия `archeology_era`** — эпохи: mezolit, neolit, bronze, iron, medieval.
- **Таксономия `archeology_type`** — типы: gorodishche, selishche, stoyanka, mogilnik, klad.
- **Мета-поля**: `archeology_coord`, `archeology_place`, `archeology_imgSrc`, `archeology_color`, `archeology_summarize`.

## REST API

| Метод | Роут | Назначение |
|---|---|---|
| GET | `/archeology/v1/objects` | Полный список памятников для карты, одним запросом |
| GET | `/archeology/v1/objects/{id}/adjacent` | Предыдущий/следующий по ID |
| GET | `/archeology/v1/objects/{id}/nearby?max_distance={km}` | До 4 ближайших по координатам |

### Формат ответа `/objects`

```json
[
  {
    "id": 1,
    "coordinates": [58.603, 49.668],
    "title": "Хлыновский кремль",
    "place": "г. Киров, исторический центр",
    "imgSrc": "",
    "color": "",
    "summarize": "",
    "content": "",
    "img": null,
    "era": "medieval",
    "type": "gorodishche"
  }
]
```

Поля `summarize` и `content` намеренно пустые в массовом ответе — деталь
подтягивается отдельным запросом к `/wp/v2/archeology/{id}?_embed=1` при
открытии страницы объекта. `era` и `type` — слаги таксономий, нужны для
клиентской фильтрации без дополнительных запросов.

## Тестовые данные

При первой активации плагина (флаг `archeology_data_seeded` в опциях)
автоматически создаются 10 памятников с реальными координатами Кировской
области, разбитые по 5 эпохам и 5 типам:

1. Шиханы-1 (мезолит, стоянка)
2. Белая Локша (неолит, стоянка)
3. Ананьинское городище (бронза, городище)
4. Буйское городище (бронза, городище)
5. Шиховское городище (железо, городище)
6. Пижемское городище (железо, городище)
7. Хлыновский кремль (средневековье, городище)
8. Котельничское городище (средневековье, городище)
9. Вятский некрополь (средневековье, могильник)
10. Клад серебряных монет (средневековье, клад)
