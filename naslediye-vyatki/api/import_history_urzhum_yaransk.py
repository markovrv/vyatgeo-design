# -*- coding: utf-8 -*-
"""
Импорт ленты истории двух городов — Уржум и Яранск (Вятушка/города/) — в
WordPress (testsite.vyatgeo.ru) через REST API плагина history. Источник и
решения по спорным местам — см. Вятушка/PLAN.md, часть Б.

Использование:
    python import_history_urzhum_yaransk.py --dry-run                 # план без записи
    python import_history_urzhum_yaransk.py --city urzhum --limit 3    # тестовый прогон
    python import_history_urzhum_yaransk.py                            # полный импорт (100+100)

Решения, согласованные с заказчиком / найденные при проверке (не изобретены
скриптом сходу — см. Вятушка/PLAN.md и отчёт агента):
  - Термины таксономии city «Уржум» (id=430) и «Яранск» (id=428) УЖЕ
    существуют на сервере (с готовым city_short, но без city_coord/city_photo)
    — скрипт не создаёт новые термины, а дополняет существующие.
  - Координаты центра городов — открытые геоданные (Википедия/mojgorod):
    Уржум 57.109738, 50.005726; Яранск 57.3029505, 47.859979.
  - Обложка города (city_photo) — герб: «Герб Уржума» (фото №17 в
    Urzhum_izobrazhenia.docx) и «Современный герб Яранска» (фото №95 в
    Yaransk_izobrazhenia.docx) — подтверждено заказчиком.
  - Номер картинки «75» у Яранска упомянут в двух строках таблицы
    («Перевод Марийского педагогического техникума...», 1929 и «Начало
    издания газеты «Яранский колхозник»», 1930), при этом подпись к
    75.jpg — про газету 1936 года. Фото относится только ко второй строке;
    первая событие остаётся без фото.
  - history_date_value: если в исходном тексте есть день+месяц+год — берётся
    точная ISO-дата (важно для сортировки внутри одного года); если только
    месяц+год — 1-е число месяца; если только сезон+год — условный месяц
    сезона; если только год — 1 января; для диапазона лет — первый год;
    для века римскими цифрами («XVI в.») — середина века. Текст даты в любом
    случае сохраняется как есть в history_date_text.

Важно: пост создаётся ОДНИМ REST-запросом со всеми мета-полями/таксономией/
featured_media сразу — history.php уже безопасен для REST (update_callback
через `$value ?? ''`, save_post термина `city` не имеет деструктивной ветки).
"""

import argparse
import glob
import os
import re
import sys

import docx
import pandas as pd
import requests

WORDPRESS_URL = 'https://testsite.vyatgeo.ru/wp-json/'
USERNAME = 'admin'
PASSWORD = 'j12M2e2qN3faOUTDGalfrYKW'

BASE = os.path.join('..', 'Вятушка', 'города')

CITIES = {
    'urzhum': {
        'term_id': 430,
        'excel': os.path.join(BASE, 'Urzhum_daty.xlsx'),
        'photo_dir': os.path.join(BASE, 'Уржум'),
        'captions_docx': os.path.join(BASE, 'Urzhum_izobrazhenia.docx'),
        'coord': '57.109738, 50.005726',
        'cover_number': '17',
    },
    'yaransk': {
        'term_id': 428,
        'excel': os.path.join(BASE, 'Yaransk_daty.xlsx'),
        'photo_dir': os.path.join(BASE, 'Яранск'),
        'captions_docx': os.path.join(BASE, 'Yaransk_izobrazhenia.docx'),
        'coord': '57.3029505, 47.859979',
        'cover_number': '95',
    },
}

# См. докстроку: подпись к 75.jpg (Яранск) относится только к событию про
# газету «Яранский колхозник», у события про техникум номер убирается.
YARANSK_STRIP_PHOTO = {
    'Перевод Марийского педагогического техникума в г. Яранск': ['75'],
}

MONTHS = {
    'январ': 1, 'феврал': 2, 'март': 3, 'апрел': 4, 'ма': 5, 'июн': 6,
    'июл': 7, 'август': 8, 'сентябр': 9, 'октябр': 10, 'ноябр': 11, 'декабр': 12,
}
SEASON_MONTH = {'зим': 1, 'весн': 3, 'лет': 6, 'осен': 9}
ROMAN = {'XIV': 14, 'XV': 15, 'XVI': 16, 'XVII': 17, 'XVIII': 18, 'XIX': 19, 'XX': 20}

# Опечатки в названиях месяцев, встреченные в источнике (см. Вятушка/PLAN.md
# не фиксирует их явно — обнаружены при разборе всех уникальных дат).
DATE_TYPO_FIXES = {
    'авугста': 'августа',
    'феврая': 'февраля',
    'феврая': 'февраля',
}

session = requests.Session()
session.auth = (USERNAME, PASSWORD)

_term_cache = {}
_media_cache = {}

DRY_RUN = False
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


def month_index(word):
    w = word.lower()
    for stem, idx in MONTHS.items():
        if w.startswith(stem):
            return idx
    return None


def parse_history_date(raw):
    """Текст источника -> ISO-дата (Y-m-d) для history_date_value.
    См. правило в докстроке модуля."""
    s = clean(raw)
    for typo, fix in DATE_TYPO_FIXES.items():
        s = s.replace(typo, fix)
    if not s:
        return None

    # Век римскими цифрами: середина века.
    m = re.match(r'^(XIV|XV|XVI|XVII|XVIII|XIX|XX)\s*в\.?$', s)
    if m:
        century = ROMAN[m.group(1)]
        mid_year = (century - 1) * 100 + 50
        return f'{mid_year:04d}-01-01'

    # Диапазон/список лет: "1594-1600 гг.", "1594–1600", "1890-е", "1680-е г."
    m = re.match(r'^(\d{4})-е\.?\s*г?\.?$', s)
    if m:
        return f'{int(m.group(1)):04d}-01-01'

    # Полная дата (день месяц год), возможно с диапазоном ("... – ...").
    first_part = re.split(r'[–—-]\s*(?=\d|[А-ЯЁ])', s, maxsplit=1)[0]
    m = re.search(r'(\d{1,2})\s+([А-Яа-яЁё]+)\s+(\d{4})', first_part)
    if m:
        day, month_word, year = m.groups()
        mi = month_index(month_word)
        if mi:
            return f'{int(year):04d}-{mi:02d}-{int(day):02d}'

    # Месяц без дня + год ("Январь 1918 г", "Ноябрь 1920 г.").
    m = re.search(r'([А-Яа-яЁё]+)\s+(\d{4})', first_part)
    if m:
        month_word, year = m.groups()
        mi = month_index(month_word)
        if mi:
            return f'{int(year):04d}-{mi:02d}-01'

    # Сезон + год ("Осень 1907 г.").
    m = re.search(r'([А-Яа-яЁё]+)\s+(\d{4})', s)
    if m:
        season_word, year = m.groups()
        sw = season_word.lower()
        for stem, mi in SEASON_MONTH.items():
            if sw.startswith(stem):
                return f'{int(year):04d}-{mi:02d}-01'

    # Просто год (первый упомянутый), включая диапазоны "1594-1600 гг.".
    m = re.search(r'(\d{4})', s)
    if m:
        return f'{int(m.group(1)):04d}-01-01'

    return None


def parse_captions(docx_path):
    """{'17': 'Герб Уржума', '9.1': '...'} — построчный разбор
    Urzhum_izobrazhenia.docx/Yaransk_izobrazhenia.docx. Эвристика (не все
    исходные строки идеально регулярны) — при неполном совпадении подпись
    просто остаётся пустой, это не блокирует импорт."""
    d = docx.Document(docx_path)
    captions = {}
    pending_key = None
    for p in d.paragraphs:
        text = p.text.strip()
        if not text or text.endswith(':'):
            continue
        m = re.match(r'^(\d+(?:\.\d+)?)[.,]?\s+(.*)$', text)
        if m and m.group(2):
            key = m.group(1)
            caption = m.group(2).strip()
            captions[key] = caption
            pending_key = key
        elif pending_key:
            captions[pending_key] = (captions.get(pending_key, '') + ' ' + text).strip()
    return captions


def build_photo_index(photo_dir):
    """Номер картинки (строкой, напр. '9.1') -> путь к файлу."""
    index = {}
    for path in glob.glob(os.path.join(photo_dir, '*.*')):
        name = os.path.basename(path)
        m = re.match(r'^([\d.]+)\.(\w+)$', name)
        if m:
            index[m.group(1)] = path
    return index


CONTENT_TYPES = {'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png', 'webp': 'image/webp'}


def upload_photo(city_key, number, photo_index):
    cache_key = (city_key, number)
    if cache_key in _media_cache:
        return _media_cache[cache_key]
    if DRY_RUN:
        _media_cache[cache_key] = (DRY_RUN_MEDIA_ID, f'dry-run://{city_key}/{number}')
        return _media_cache[cache_key]

    path = photo_index.get(number)
    if not path:
        log(f'  ! нет файла для номера {number} ({city_key})')
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
        log(f'  ! не удалось загрузить {filename}: {resp.status_code} {resp.text[:200]}')
        return None
    data = resp.json()
    result = (data['id'], data.get('source_url', ''))
    _media_cache[cache_key] = result
    return result


def post_exists(post_type, title):
    resp = session.get(
        f'{WORDPRESS_URL}wp/v2/{post_type}',
        params={'search': title, 'per_page': 100, 'status': 'publish,draft'},
    )
    if resp.status_code != 200:
        return None
    import html as html_mod
    for post in resp.json():
        rendered = html_mod.unescape(post.get('title', {}).get('rendered', '')).strip()
        if rendered == title.strip():
            return post['id']
    return None


def create_post(post_type, payload):
    resp = session.post(f'{WORDPRESS_URL}wp/v2/{post_type}', json=payload)
    if resp.status_code not in (200, 201):
        raise RuntimeError(f'HTTP {resp.status_code}: {resp.text[:300]}')
    return resp.json()


# --------------------------------------------------------------- city setup --

def ensure_city_meta(city_key):
    cfg = CITIES[city_key]
    log(f'=== Обложка и координаты города «{city_key}» (id={cfg["term_id"]}) ===')
    photo_index = build_photo_index(cfg['photo_dir'])
    media = upload_photo(city_key, cfg['cover_number'], photo_index)
    if media is None:
        log('  ! не удалось получить обложку, city_photo не будет обновлён')
        media_id = None
    else:
        media_id, _ = media

    if DRY_RUN:
        log(f'  DRY-RUN: city_coord -> {cfg["coord"]!r}, city_photo -> {media_id!r}')
        return

    payload = {'meta': {'city_coord': cfg['coord']}}
    if media_id:
        payload['meta']['city_photo'] = media_id
    resp = session.post(f'{WORDPRESS_URL}wp/v2/city/{cfg["term_id"]}', json=payload)
    if resp.status_code not in (200, 201):
        log(f'  ! не удалось обновить термин города: {resp.status_code} {resp.text[:200]}')
    else:
        log(f'  обновлено: city_coord={cfg["coord"]!r}, city_photo={media_id!r}')


# --------------------------------------------------------------- content ----

def build_event_content(text, numbers, photo_index, captions, city_key):
    parts = [f'<p>{text}</p>'] if text else []
    featured_media = None
    for number in numbers:
        media = upload_photo(city_key, number, photo_index)
        if media is None:
            continue
        media_id, url = media
        if featured_media is None:
            featured_media = media_id
        caption = captions.get(number, '')
        fig = f'<figure><img src="{url}">'
        if caption:
            fig += f'<figcaption>{caption}</figcaption>'
        fig += '</figure>'
        parts.append(fig)
    return '\n'.join(parts), featured_media


def import_city_events(city_key, limit=None, dry_run=False):
    cfg = CITIES[city_key]
    log(f'\n=== История: {city_key} ===')
    df = pd.read_excel(cfg['excel'])
    rows = df.to_dict('records')
    if limit:
        rows = rows[:limit]

    photo_index = build_photo_index(cfg['photo_dir'])
    captions = parse_captions(cfg['captions_docx'])

    log(f'К обработке: {len(rows)}')

    created = skipped = errors = 0
    for i, row in enumerate(rows, 1):
        title = clean(row.get('заголовок'))
        text = clean(row.get('текст'))
        date_raw = clean(row.get('дата'))
        numbers_raw = clean(row.get('номер картинки'))
        numbers = [n.strip() for n in re.split(r'[,;]', numbers_raw) if n.strip()]

        strip_list = YARANSK_STRIP_PHOTO.get(title, [])
        numbers = [n for n in numbers if n not in strip_list]

        if not title:
            continue

        content, featured_media = build_event_content(text, numbers, photo_index, captions, city_key)
        date_value = parse_history_date(date_raw)

        payload = {
            'title': title,
            'content': content,
            'status': 'publish',
            'history_date_text': date_raw,
            'city': [cfg['term_id']],
        }
        if date_value:
            payload['history_date_value'] = date_value
        if featured_media:
            payload['featured_media'] = featured_media

        existing_id = post_exists('history', title)
        if existing_id:
            log(f'[{i}/{len(rows)}] пропуск (уже есть id={existing_id}): {title}')
            skipped += 1
            continue

        if dry_run:
            log(f'[{i}/{len(rows)}] DRY-RUN: {title} (дата={date_raw!r} -> {date_value}, '
                f'фото={numbers})')
            continue

        try:
            result = create_post('history', payload)
            log(f'[{i}/{len(rows)}] создан id={result["id"]}: {title}')
            created += 1
        except Exception as e:
            log(f'[{i}/{len(rows)}] ОШИБКА: {title}: {e}')
            errors += 1

    log(f'\n{city_key} — создано: {created}, пропущено: {skipped}, ошибок: {errors}')
    return created, skipped, errors


# ------------------------------------------------------------------ main --

def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--city', choices=['urzhum', 'yaransk', 'all'], default='all')
    parser.add_argument('--limit', type=int, default=None, help='ограничить число событий на город (для теста)')
    parser.add_argument('--dry-run', action='store_true', help='ничего не создавать, только показать план')
    args = parser.parse_args()

    global DRY_RUN
    DRY_RUN = args.dry_run

    log('=== Проверка подключения к WordPress ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/users/me')
    resp.raise_for_status()
    log(f'Авторизован как: {resp.json().get("name")}')

    if not args.dry_run:
        confirm = input(f'Начать импорт ({args.city}, limit={args.limit or "все"})? (y/n): ').strip().lower()
        if confirm not in ('y', 'yes', 'д', 'да'):
            log('Отменено пользователем.')
            sys.exit(0)

    cities = ['urzhum', 'yaransk'] if args.city == 'all' else [args.city]
    for city_key in cities:
        ensure_city_meta(city_key)
    for city_key in cities:
        import_city_events(city_key, limit=args.limit, dry_run=args.dry_run)


if __name__ == '__main__':
    main()
