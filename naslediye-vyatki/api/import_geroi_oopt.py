# -*- coding: utf-8 -*-
"""
Импорт данных «Герои Вятского края» (Лист1) и «Памятники природы Кировской
области» (Лист2) из Geroi.xlsx в WordPress (testsite.vyatgeo.ru) через REST
API плагинов geroi-vyatki/oopt-kirov.

Использование:
    python import_geroi_oopt.py --dry-run              # план без записи
    python import_geroi_oopt.py --only hero --limit 3  # тестовый прогон
    python import_geroi_oopt.py                        # полный импорт (221 герой + 17 ООПТ)

Важно: пост создаётся ОДНИМ REST-запросом со всеми мета-полями, таксономиями
и featured_media сразу. Это не стилистический выбор, а обязательное условие:
save_post-хук в register-hero.php/register-oopt.php читает $_POST, который
всегда пуст при запросах через REST API — если сначала создать пост, а потом
дополнить его отдельным запросом (например, проставить таксономии), первый же
такой follow-up-запрос обнулит все остальные мета-поля до пустой строки. Хуки
поправлены (REST_REQUEST-guard), но фикс ещё не выложен на сервер — пока он не
задеплоен, скрипт обязан обходиться без каких-либо повторных запросов к уже
созданному посту.
"""

import argparse
import html
import io
import random
import re
import sys
from datetime import datetime

import pandas as pd
import requests
from PIL import Image, ImageDraw

WORDPRESS_URL = 'https://testsite.vyatgeo.ru/wp-json/'
USERNAME = 'admin'
PASSWORD = 'j12M2e2qN3faOUTDGalfrYKW'
EXCEL_FILE = 'Geroi.xlsx'

# Примерные границы территории Кирова — для героев/ООПТ без координат в источнике.
KIROV_LAT_RANGE = (58.545, 58.660)
KIROV_LNG_RANGE = (49.520, 49.750)

HERO_STATUS_COLORS = {
    'историческая': '#C27E3A',
    'современная': '#3C7A8C',
}
OOPT_PROFILE_COLORS = {
    'гидрологический': '#3C7A8C',
    'ботанический': '#4F7942',
    'геологический': '#8A6D3B',
}

# Колонки Лист1 — переносить 1:1, чтобы не гадать при правках источника.
COL_H_PHOTO = 'Фото'
COL_H_FIO = 'ФИО'
COL_H_BIRTH_DATE = 'Дата рождения'
COL_H_DEATH_DATE = 'Дата смерти (если есть)/ если жив ставить NULL'
COL_H_BIRTH_PLACE = 'Место рождения (текстовое описание: населённый пункт, район)'
COL_H_DEATH_PLACE = 'Место смерти (если есть)/ если жив ставить NULL'
COL_H_FEAT = 'Описание подвига, заслуг, вклада'
COL_H_AWARDS = 'Перечень наград (ордена, медали, звания)'
COL_H_PROFESSION = 'Профессия, деятельность в мирное время (если известно)'
COL_H_CONNECTION = 'Характер связи с Вятским краем: «родился», «жил», «работал», «совершил подвиг», «похоронен» и т.п.'
COL_H_COORD = 'Координаты места в Вятском крае'
COL_H_STATUS = 'Статус персоналии: «историческая», «современная» (ныне живущий)'
COL_H_BIO = 'Литературная Биография'

# Колонки Лист2.
COL_O_COORD = 'Координаты'
COL_O_PHOTO = 'Фото'
COL_O_DISTRICT = 'Район'
COL_O_NAME = 'Название ООПТ'
COL_O_PROFILE = 'Профиль'
COL_O_AREA = 'Площадь, га'
COL_O_SPECIES = 'Охраняемые виды'
COL_O_SUMMARY = 'Краткое описание'
COL_O_LITERARY = 'Литературное описание'

session = requests.Session()
session.auth = (USERNAME, PASSWORD)

_term_cache = {}       # (taxonomy, name.lower()) -> term_id
_placeholder_media_id = None

# В dry-run режиме ни один запрос на запись (создание термина, загрузка
# медиа, создание поста) не должен уходить на сервер — иначе "план без
# записи" на деле создаёт реальные термины таксономий (так уже случилось при
# первом прогоне: 2 hero_status + 100 hero_connection ушли в БД до того, как
# сухой запуск дошёл до самого первого пропущенного create_post).
DRY_RUN = False
DRY_RUN_TERM_ID = -1
DRY_RUN_MEDIA_ID = -1


def log(msg):
    print(msg, flush=True)


# ---------------------------------------------------------------- helpers --

def clean(value):
    """Строка без NaN/None/неразрывных пробелов, или '' если значения нет."""
    if value is None:
        return ''
    try:
        if pd.isna(value):
            return ''
    except (TypeError, ValueError):
        pass
    s = str(value).replace('\xa0', ' ').strip()
    return '' if s.lower() == 'nan' else s


def has_real_text(value):
    """Отсекает случайно вклеенное чужое ФИО вместо текста биографии.

    В Лист1 (строки ~80-104) часть ячеек «Литературная Биография» содержит не
    текст, а имя другого героя из этой же таблицы — судя по всему, сдвиг при
    копировании колонки. У всех обнаруженных подделок нет ни одной точки и
    3-4 слова; у настоящих биографий всегда есть точки (это полноценный
    текст на несколько предложений). Проверено на всех 221 строке.
    """
    s = clean(value)
    return bool(s) and '.' in s


def random_kirov_coord():
    lat = round(random.uniform(*KIROV_LAT_RANGE), 6)
    lng = round(random.uniform(*KIROV_LNG_RANGE), 6)
    return f'{lat}, {lng}'


def parse_coord(raw):
    """'(Приблизительно) 58.6, 49.6' -> '58.6, 49.6'; при отсутствии — None."""
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
    """dd.mm.yyyy где возможно; частичная дата (год) — строкой; None — если
    даты нет вовсе или явно указано NULL (герой жив)."""
    if value is None:
        return None
    try:
        if pd.isna(value):
            return None
    except (TypeError, ValueError):
        pass
    if hasattr(value, 'strftime'):
        return value.strftime('%d.%m.%Y')
    if isinstance(value, (int, float)):
        return str(int(value))
    s = clean(value)
    if not s or s.upper() == 'NULL':
        return None
    m = re.match(r'^(\d{4})-(\d{2})-(\d{2})', s)
    if m:
        y, mo, d = m.groups()
        return f'{d}.{mo}.{y}'
    return s


def decode_title(rendered):
    return html.unescape(rendered or '').strip()


def get_or_create_placeholder_media():
    """Один и тот же плейсхолдер грузится один раз и переиспользуется как
    featured_media у всех записей без фото (в источнике колонка «Фото» пуста
    у 100% строк на обоих листах)."""
    global _placeholder_media_id
    if _placeholder_media_id is not None:
        return _placeholder_media_id
    if DRY_RUN:
        return DRY_RUN_MEDIA_ID

    img = Image.new('RGB', (800, 600), color=(196, 184, 156))
    draw = ImageDraw.Draw(img)
    draw.rectangle([40, 40, 760, 560], outline=(120, 100, 70), width=3)
    draw.text((280, 285), 'Фото уточняется', fill=(60, 48, 32))
    buf = io.BytesIO()
    img.save(buf, format='JPEG', quality=85)
    buf.seek(0)

    resp = session.post(
        f'{WORDPRESS_URL}wp/v2/media',
        files={'file': ('placeholder.jpg', buf, 'image/jpeg')},
        headers={'Content-Disposition': 'attachment; filename="placeholder.jpg"'},
    )
    resp.raise_for_status()
    _placeholder_media_id = resp.json()['id']
    log(f'  [медиа] тестовое фото-плейсхолдер загружено, id={_placeholder_media_id}')
    return _placeholder_media_id


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


# ------------------------------------------------------------------ hero --

def build_hero_payload(row):
    fio = clean(row.get(COL_H_FIO))
    if not fio:
        return None

    coord = parse_coord(row.get(COL_H_COORD))
    coord_was_missing = coord is None
    if coord is None:
        coord = random_kirov_coord()

    bio_raw = row.get(COL_H_BIO)
    content = clean(bio_raw) if has_real_text(bio_raw) else ''

    payload = {
        'title': fio,
        'content': content,
        'status': 'publish',
        'hero_coord': coord,
        'hero_birth_place': clean(row.get(COL_H_BIRTH_PLACE)),
        'hero_death_place': clean(row.get(COL_H_DEATH_PLACE)),
        'hero_feat_description': clean(row.get(COL_H_FEAT)),
        'hero_awards': clean(row.get(COL_H_AWARDS)),
        'hero_profession': clean(row.get(COL_H_PROFESSION)),
        'hero_imgSrc': '',
        'featured_media': get_or_create_placeholder_media(),
    }

    birth_date = normalize_date(row.get(COL_H_BIRTH_DATE))
    if birth_date:
        payload['hero_birth_date'] = birth_date
    death_date = normalize_date(row.get(COL_H_DEATH_DATE))
    if death_date:
        payload['hero_death_date'] = death_date

    status_raw = clean(row.get(COL_H_STATUS))
    status_key = status_raw.lower()
    if status_raw:
        term_id = get_or_create_term('hero_status', status_raw.capitalize())
        if term_id:
            payload['hero_status'] = [term_id]
    payload['hero_color'] = HERO_STATUS_COLORS.get(status_key, '')

    payload['hero_connection'] = clean(row.get(COL_H_CONNECTION))

    return payload, coord_was_missing


def import_heroes(limit=None, dry_run=False, skip_existing=True):
    log('\n=== Герои Вятского края (Лист1) ===')
    df = pd.read_excel(EXCEL_FILE, sheet_name='Лист1')
    rows = df.to_dict('records')

    # Дедупликация по ФИО: в источнике 6 персоналий продублированы (строки
    # ~75-86), у одной копии текст биографии отсутствует/повреждён (см.
    # has_real_text) — оставляем ту копию, где текст настоящий.
    dedup = {}
    for row in rows:
        fio = clean(row.get(COL_H_FIO))
        if not fio:
            continue
        prev = dedup.get(fio)
        if prev is None:
            dedup[fio] = row
        elif has_real_text(row.get(COL_H_BIO)) and not has_real_text(prev.get(COL_H_BIO)):
            dedup[fio] = row

    named_rows = sum(1 for row in rows if clean(row.get(COL_H_FIO)))
    items = list(dedup.values())
    if limit:
        items = items[:limit]

    log(f'К обработке: {len(items)} (после дедупликации из {named_rows} именованных строк '
        f'листа; {len(rows)} строк всего, включая пустой хвост)')

    created = skipped = errors = coord_fallback = 0
    for i, row in enumerate(items, 1):
        fio = clean(row.get(COL_H_FIO))
        built = build_hero_payload(row)
        if built is None:
            continue
        payload, coord_was_missing = built
        if coord_was_missing:
            coord_fallback += 1

        if skip_existing:
            existing_id = post_exists('hero', fio)
            if existing_id:
                log(f'[{i}/{len(items)}] пропуск (уже есть id={existing_id}): {fio}')
                skipped += 1
                continue

        if dry_run:
            log(f'[{i}/{len(items)}] DRY-RUN: {fio} (coord={payload["hero_coord"]}, '
                f'случайные={coord_was_missing})')
            continue

        try:
            result = create_post('hero', payload)
            log(f'[{i}/{len(items)}] создан id={result["id"]}: {fio}')
            created += 1
        except Exception as e:
            log(f'[{i}/{len(items)}] ОШИБКА: {fio}: {e}')
            errors += 1

    log(f'\nГерои — создано: {created}, пропущено (уже были): {skipped}, ошибок: {errors}, '
        f'координаты сгенерированы случайно: {coord_fallback}')
    return created, skipped, errors


# ------------------------------------------------------------------ oopt --

def parse_species(raw):
    """'Вид (статус), Вид2' -> [(имя, статус), ...]; служебное значение
    «Редких видов не выявлено» — не название вида, пропускается."""
    raw = clean(raw)
    if not raw:
        return []
    items = []
    for part in raw.split(','):
        part = part.strip()
        if not part or part.lower().startswith('редких видов не выявлено'):
            continue
        m = re.match(r'^(.*?)\s*\(([^)]+)\)\s*$', part)
        if m:
            items.append((m.group(1).strip(), m.group(2).strip()))
        else:
            items.append((part, ''))
    return items


def clean_literary_description(raw):
    """8 из 17 строк Лист2 хранят «{название} - {краткое описание};{текст}»
    — берём то, что после первой ';' (реальный литературный текст)."""
    s = clean(raw)
    if ';' in s:
        s = s.split(';', 1)[1].strip()
    return s


def build_oopt_payload(row):
    name = clean(row.get(COL_O_NAME))
    if not name:
        return None

    coord = parse_coord(row.get(COL_O_COORD))
    coord_was_missing = coord is None
    if coord is None:
        coord = random_kirov_coord()

    payload = {
        'title': name,
        'content': clean_literary_description(row.get(COL_O_LITERARY)),
        'status': 'publish',
        'oopt_coord': coord,
        'oopt_summary': clean(row.get(COL_O_SUMMARY)),
        'oopt_imgSrc': '',
        'featured_media': get_or_create_placeholder_media(),
    }

    area_raw = clean(row.get(COL_O_AREA))
    if area_raw:
        try:
            payload['oopt_area'] = float(area_raw.replace(',', '.'))
        except ValueError:
            payload['oopt_area'] = area_raw

    district_raw = clean(row.get(COL_O_DISTRICT))
    if district_raw:
        term_id = get_or_create_term('oopt_district', district_raw)
        if term_id:
            payload['oopt_district'] = [term_id]

    profile_raw = clean(row.get(COL_O_PROFILE))
    if profile_raw:
        term_id = get_or_create_term('oopt_profile', profile_raw)
        if term_id:
            payload['oopt_profile'] = [term_id]
    payload['oopt_color'] = OOPT_PROFILE_COLORS.get(profile_raw.lower(), '')

    species_ids = []
    for sp_name, sp_status in parse_species(row.get(COL_O_SPECIES)):
        meta = {'oopt_species_status': sp_status} if sp_status else None
        term_id = get_or_create_term('oopt_species', sp_name, meta=meta)
        if term_id:
            species_ids.append(term_id)
    if species_ids:
        payload['oopt_species'] = species_ids

    return payload, coord_was_missing


def import_oopt(limit=None, dry_run=False, skip_existing=True):
    log('\n=== Памятники природы Кировской области (Лист2) ===')
    df = pd.read_excel(EXCEL_FILE, sheet_name='Лист2')
    rows = [r for r in df.to_dict('records') if clean(r.get(COL_O_NAME))]
    if limit:
        rows = rows[:limit]

    log(f'К обработке: {len(rows)}')

    created = skipped = errors = coord_fallback = 0
    for i, row in enumerate(rows, 1):
        name = clean(row.get(COL_O_NAME))
        built = build_oopt_payload(row)
        if built is None:
            continue
        payload, coord_was_missing = built
        if coord_was_missing:
            coord_fallback += 1

        if skip_existing:
            existing_id = post_exists('oopt', name)
            if existing_id:
                log(f'[{i}/{len(rows)}] пропуск (уже есть id={existing_id}): {name}')
                skipped += 1
                continue

        if dry_run:
            log(f'[{i}/{len(rows)}] DRY-RUN: {name} (coord={payload["oopt_coord"]}, '
                f'случайные={coord_was_missing})')
            continue

        try:
            result = create_post('oopt', payload)
            log(f'[{i}/{len(rows)}] создан id={result["id"]}: {name}')
            created += 1
        except Exception as e:
            log(f'[{i}/{len(rows)}] ОШИБКА: {name}: {e}')
            errors += 1

    log(f'\nПамятники природы — создано: {created}, пропущено (уже были): {skipped}, '
        f'ошибок: {errors}, координаты сгенерированы случайно: {coord_fallback}')
    return created, skipped, errors


# ------------------------------------------------------------------ main --

def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--only', choices=['hero', 'oopt', 'all'], default='all')
    parser.add_argument('--limit', type=int, default=None, help='ограничить число записей на каждый лист (для теста)')
    parser.add_argument('--dry-run', action='store_true', help='ничего не создавать, только показать план')
    parser.add_argument('--no-skip-existing', action='store_true', help='не проверять дубликаты по заголовку')
    args = parser.parse_args()

    global DRY_RUN
    DRY_RUN = args.dry_run

    log('=== Проверка подключения к WordPress ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/users/me')
    resp.raise_for_status()
    log(f'Авторизован как: {resp.json().get("name")}')

    if not args.dry_run:
        confirm = input(f'Начать импорт ({args.only}, limit={args.limit or "все"})? (y/n): ').strip().lower()
        if confirm not in ('y', 'yes', 'д', 'да'):
            log('Отменено пользователем.')
            sys.exit(0)

    if args.only in ('hero', 'all'):
        import_heroes(limit=args.limit, dry_run=args.dry_run, skip_existing=not args.no_skip_existing)
    if args.only in ('oopt', 'all'):
        import_oopt(limit=args.limit, dry_run=args.dry_run, skip_existing=not args.no_skip_existing)


if __name__ == '__main__':
    main()
