# -*- coding: utf-8 -*-
"""
Импорт 130 новых персоналий («Вятушка/герои/Для Алексея Кайсина/Geroi.xlsx»,
Лист1 без заголовка) в WordPress (testsite.vyatgeo.ru) через REST API плагина
geroi-vyatki. Источник и решения по спорным местам — см. Вятушка/PLAN.md.

Использование:
    python import_geroi_novye_biografii.py --dry-run              # план без записи
    python import_geroi_novye_biografii.py --limit 3               # тестовый прогон
    python import_geroi_novye_biografii.py                         # полный импорт (130 строк)

Решения, согласованные с заказчиком (не изобретены скриптом):
  - Схема hero_status расширена до 3 терминов вместо конвертации новых данных
    к старой схеме из 2: существующие термины 896 «Историческая» и 897
    «Современная» переименовываются в «Историческая личность» и «Современник»
    (id и, соответственно, привязка у всех текущих 213/1 героев не меняются —
    это чистое переименование лейбла), плюс создаётся новый термин «Жив».
  - Часть из 130 персоналий может уже существовать на сервере (проверка по
    названию в Geroi.xlsx из api/ была офлайн и могла не отразить более
    свежее состояние продакшена) — если пост с таким заголовком уже есть,
    скрипт его ОБНОВЛЯЕТ (PATCH), а не пропускает и не дублирует.

Важно: пост создаётся/обновляется ОДНИМ REST-запросом со всеми мета-полями,
таксономией и featured_media сразу — так же, как в import_geroi_oopt.py.
save_post-хук в register-hero.php уже содержит REST_REQUEST-guard (проверено
живым запросом к продакшену), поэтому и PATCH существующего поста, и POST
нового безопасны в один запрос.
"""

import argparse
import glob
import html
import os
import re
import sys

import pandas as pd
import requests

WORDPRESS_URL = 'https://testsite.vyatgeo.ru/wp-json/'
USERNAME = 'admin'
PASSWORD = 'j12M2e2qN3faOUTDGalfrYKW'

EXCEL_FILE = os.path.join('..', 'Вятушка', 'герои', 'Для Алексея Кайсина', 'Geroi.xlsx')
PHOTO_DIR = os.path.join('..', 'Вятушка', 'герои', 'Для Алексея Кайсина')

# Колонки без заголовка, индексация с 0 — см. таблицу маппинга в Вятушка/PLAN.md.
COL_FIO = 0
COL_BIRTH_DATE = 1
COL_DEATH_DATE = 2
COL_BIRTH_PLACE = 3
COL_DEATH_PLACE = 4
COL_FEAT = 5
COL_AWARDS = 6
COL_PROFESSION = 7
COL_CONNECTION = 8
COL_COORD = 9
COL_STATUS = 10

# Опечатки в статусе — исправляются независимо от схемы (см. PLAN.md, часть А, вопрос 1).
STATUS_TYPO_FIXES = {
    'исторрическая личность': 'Историческая личность',
    'истторическая личность': 'Историческая личность',
}

HERO_STATUS_RENAME = {
    896: 'Историческая личность',   # было «Историческая»
    897: 'Современник',              # было «Современная»
}
HERO_STATUS_NEW_TERM = 'Жив'

HERO_STATUS_COLORS = {
    'историческая личность': '#C27E3A',
    'современник': '#3C7A8C',
    'жив': '#8B5E2B',
}

# 8 правок дат из Примечания.docx — ключ: номер строки (=номер фото), значение
# нормализовано в формат normalize_date() (см. PLAN.md, часть А, вопрос 3).
MANUAL_DATE_FIXES = {
    64: ('death', '1974'),
    67: ('death', '1938'),
    91: ('death', '1963'),
    98: ('death', '1971'),
    103: ('death', '26.05.2024'),
    112: ('death', '17.01.2011'),
    113: ('birth', '1947'),
    115: ('birth', '1927'),
}

session = requests.Session()
session.auth = (USERNAME, PASSWORD)

_term_cache = {}
_hero_status_ready = False

DRY_RUN = False
DRY_RUN_TERM_ID = -1
DRY_RUN_MEDIA_ID = -1


def log(msg):
    print(msg, flush=True)


# ---------------------------------------------------------------- helpers --

def clean(value):
    if value is None:
        return ''
    try:
        if pd.isna(value):
            return ''
    except (TypeError, ValueError):
        pass
    s = str(value).replace('\xa0', ' ').strip()
    return '' if s.lower() == 'nan' else s


def parse_coord(raw):
    s = clean(raw)
    if not s:
        return None
    s = re.sub(r'\(.*?\)', '', s).strip()
    parts = [p.strip() for p in s.split(',') if p.strip()]
    if len(parts) < 2:
        return None
    try:
        lat, lng = float(parts[0]), float(parts[1])
    except ValueError:
        return None
    return f'{lat}, {lng}'


def normalize_date(value):
    """dd.mm.yyyy где возможно; частичная дата (год) — строкой; '' — если
    даты нет вовсе (для явной очистки поля при обновлении существующего
    поста, см. докстроку модуля)."""
    if value is None:
        return ''
    try:
        if pd.isna(value):
            return ''
    except (TypeError, ValueError):
        pass
    if hasattr(value, 'strftime'):
        return value.strftime('%d.%m.%Y')
    if isinstance(value, (int, float)):
        return str(int(value))
    s = clean(value)
    if not s or s.upper() == 'NULL':
        return ''
    m = re.match(r'^(\d{4})-(\d{2})-(\d{2})', s)
    if m:
        y, mo, d = m.groups()
        return f'{d}.{mo}.{y}'
    return s


def decode_title(rendered):
    return html.unescape(rendered or '').strip()


def fix_status_typo(raw):
    key = raw.strip().lower()
    return STATUS_TYPO_FIXES.get(key, raw.strip())


def build_photo_index():
    """Номер строки (=номер фото) -> путь к файлу, расширение любое."""
    index = {}
    for path in glob.glob(os.path.join(PHOTO_DIR, '*.*')):
        name = os.path.basename(path)
        m = re.match(r'^(\d+)\.(\w+)$', name)
        if m:
            index[int(m.group(1))] = path
    return index


PHOTO_INDEX = build_photo_index()

CONTENT_TYPES = {
    'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png', 'webp': 'image/webp',
}


def upload_hero_photo(row_number):
    if DRY_RUN:
        return DRY_RUN_MEDIA_ID
    path = PHOTO_INDEX.get(row_number)
    if not path:
        log(f'  ! нет файла фото для строки {row_number}')
        return None
    ext = path.rsplit('.', 1)[1].lower()
    mime = CONTENT_TYPES.get(ext, 'application/octet-stream')
    filename = os.path.basename(path)
    with open(path, 'rb') as f:
        resp = session.post(
            f'{WORDPRESS_URL}wp/v2/media',
            files={'file': (filename, f, mime)},
            headers={'Content-Disposition': f'attachment; filename="{filename}"'},
        )
    if resp.status_code not in (200, 201):
        log(f'  ! не удалось загрузить фото {filename}: {resp.status_code} {resp.text[:200]}')
        return None
    return resp.json()['id']


def get_or_create_term(taxonomy, name, meta=None):
    name = clean(name)
    if not name:
        return None
    key = (taxonomy, name.lower())
    if key in _term_cache:
        return _term_cache[key]
    if DRY_RUN:
        return DRY_RUN_TERM_ID

    resp = session.get(f'{WORDPRESS_URL}wp/v2/{taxonomy}', params={'search': name, 'per_page': 100})
    resp.raise_for_status()
    for term in resp.json():
        if term['name'].strip().lower() == name.lower():
            _term_cache[key] = term['id']
            return term['id']

    payload = {'name': name}
    if meta:
        payload['meta'] = meta
    resp = session.post(f'{WORDPRESS_URL}wp/v2/{taxonomy}', json=payload)
    if resp.status_code not in (200, 201):
        log(f'  ! не удалось создать термин {taxonomy}:{name!r} — {resp.status_code} {resp.text[:200]}')
        return None
    term_id = resp.json()['id']
    _term_cache[key] = term_id
    return term_id


def ensure_hero_status_scheme():
    """Переименовывает термины 896/897 в новую схему и создаёт «Жив».
    Идемпотентно — повторный запуск ничего не ломает."""
    global _hero_status_ready
    if _hero_status_ready:
        return
    _hero_status_ready = True

    log('=== Приведение hero_status к схеме из 3 терминов ===')
    if DRY_RUN:
        for term_id, new_name in HERO_STATUS_RENAME.items():
            log(f'  DRY-RUN: переименовать термин {term_id} -> {new_name!r}')
        log(f'  DRY-RUN: создать термин {HERO_STATUS_NEW_TERM!r}, если его ещё нет')
        for new_name in list(HERO_STATUS_RENAME.values()) + [HERO_STATUS_NEW_TERM]:
            _term_cache[('hero_status', new_name.lower())] = DRY_RUN_TERM_ID
        return

    for term_id, new_name in HERO_STATUS_RENAME.items():
        resp = session.get(f'{WORDPRESS_URL}wp/v2/hero_status/{term_id}')
        if resp.status_code != 200:
            log(f'  ! термин {term_id} не найден на сервере — {resp.status_code}')
            continue
        current_name = resp.json().get('name', '')
        if current_name == new_name:
            log(f'  термин {term_id} уже называется {new_name!r}, пропуск')
        else:
            resp = session.post(f'{WORDPRESS_URL}wp/v2/hero_status/{term_id}', json={'name': new_name})
            if resp.status_code not in (200, 201):
                log(f'  ! не удалось переименовать термин {term_id}: {resp.status_code} {resp.text[:200]}')
                continue
            log(f'  термин {term_id}: {current_name!r} -> {new_name!r}')
        _term_cache[('hero_status', new_name.lower())] = term_id

    new_id = get_or_create_term('hero_status', HERO_STATUS_NEW_TERM)
    if new_id:
        log(f'  термин «{HERO_STATUS_NEW_TERM}»: id={new_id}')


def post_exists(post_type, title):
    resp = session.get(
        f'{WORDPRESS_URL}wp/v2/{post_type}',
        params={'search': title, 'per_page': 100, 'status': 'publish,draft'},
    )
    if resp.status_code != 200:
        return None
    for post in resp.json():
        if decode_title(post.get('title', {}).get('rendered', '')) == title.strip():
            return post['id']
    return None


def create_post(post_type, payload):
    resp = session.post(f'{WORDPRESS_URL}wp/v2/{post_type}', json=payload)
    if resp.status_code not in (200, 201):
        raise RuntimeError(f'HTTP {resp.status_code}: {resp.text[:300]}')
    return resp.json()


def update_post(post_type, post_id, payload):
    resp = session.post(f'{WORDPRESS_URL}wp/v2/{post_type}/{post_id}', json=payload)
    if resp.status_code not in (200, 201):
        raise RuntimeError(f'HTTP {resp.status_code}: {resp.text[:300]}')
    return resp.json()


# ------------------------------------------------------------------ hero --

def build_hero_payload(row, row_number):
    fio = clean(row.get(COL_FIO))
    if not fio:
        return None

    coord = parse_coord(row.get(COL_COORD))
    coord_was_missing = coord is None
    if coord is None:
        # В этом источнике координаты заполнены у всех 130 строк (проверено
        # в PLAN.md) — но на случай будущей неполной правки не блокируем импорт.
        coord = ''

    birth_date = normalize_date(row.get(COL_BIRTH_DATE))
    death_date = normalize_date(row.get(COL_DEATH_DATE))

    fix = MANUAL_DATE_FIXES.get(row_number)
    if fix:
        kind, value = fix
        if kind == 'birth':
            birth_date = value
        else:
            death_date = value

    status_raw = fix_status_typo(clean(row.get(COL_STATUS)))
    status_key = status_raw.lower()

    payload = {
        'title': fio,
        'content': '',
        'status': 'publish',
        'hero_coord': coord,
        'hero_birth_place': clean(row.get(COL_BIRTH_PLACE)),
        'hero_death_place': clean(row.get(COL_DEATH_PLACE)),
        'hero_feat_description': clean(row.get(COL_FEAT)),
        'hero_awards': clean(row.get(COL_AWARDS)),
        'hero_profession': clean(row.get(COL_PROFESSION)),
        'hero_connection': clean(row.get(COL_CONNECTION)),
        'hero_birth_date': birth_date,
        'hero_death_date': death_date,
        'hero_imgSrc': '',
        'hero_color': HERO_STATUS_COLORS.get(status_key, ''),
    }

    if status_raw:
        term_id = get_or_create_term('hero_status', status_raw)
        if term_id:
            payload['hero_status'] = [term_id]

    media_id = upload_hero_photo(row_number)
    if media_id:
        payload['featured_media'] = media_id

    return payload, coord_was_missing


def import_heroes(limit=None, dry_run=False, start_row=1):
    log('\n=== Герои Вятского края — новые 130 персоналий (Вятушка) ===')
    df = pd.read_excel(EXCEL_FILE, sheet_name=0, header=None)
    rows = list(df.to_dict('records'))
    rows = list(enumerate(rows, 1))  # (row_number, row) — номер = номер фото
    rows = [(n, r) for n, r in rows if n >= start_row]
    if limit:
        rows = rows[:limit]

    log(f'К обработке: {len(rows)} (начиная со строки {start_row})')

    created = updated = errors = coord_missing = 0
    for i, (row_number, row) in enumerate(rows, 1):
        fio = clean(row.get(COL_FIO))
        if not fio:
            continue

        ensure_hero_status_scheme()

        built = build_hero_payload(row, row_number)
        if built is None:
            continue
        payload, coord_was_missing = built
        if coord_was_missing:
            coord_missing += 1

        existing_id = post_exists('hero', fio)

        if dry_run:
            action = f'ОБНОВЛЕНИЕ id={existing_id}' if existing_id else 'СОЗДАНИЕ'
            log(f'[{i}/{len(rows)}] DRY-RUN {action}: {fio} '
                f'(status={payload.get("hero_status")}, coord_missing={coord_was_missing})')
            continue

        try:
            if existing_id:
                update_post('hero', existing_id, payload)
                log(f'[{i}/{len(rows)}] обновлён id={existing_id}: {fio}')
                updated += 1
            else:
                result = create_post('hero', payload)
                log(f'[{i}/{len(rows)}] создан id={result["id"]}: {fio}')
                created += 1
        except Exception as e:
            log(f'[{i}/{len(rows)}] ОШИБКА: {fio}: {e}')
            errors += 1

    log(f'\nГерои — создано: {created}, обновлено: {updated}, ошибок: {errors}, '
        f'без координат в источнике: {coord_missing}')
    return created, updated, errors


# ------------------------------------------------------------------ main --

def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--limit', type=int, default=None, help='ограничить число строк (для теста)')
    parser.add_argument('--start-row', type=int, default=1, help='с какой строки начать (1-индексация, для докачки после частичного прогона)')
    parser.add_argument('--dry-run', action='store_true', help='ничего не создавать/обновлять, только показать план')
    args = parser.parse_args()

    global DRY_RUN
    DRY_RUN = args.dry_run

    log('=== Проверка подключения к WordPress ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/users/me')
    resp.raise_for_status()
    log(f'Авторизован как: {resp.json().get("name")}')

    if not args.dry_run:
        confirm = input(f'Начать импорт (limit={args.limit or "все 130"})? (y/n): ').strip().lower()
        if confirm not in ('y', 'yes', 'д', 'да'):
            log('Отменено пользователем.')
            sys.exit(0)

    import_heroes(limit=args.limit, dry_run=args.dry_run, start_row=args.start_row)


if __name__ == '__main__':
    main()
