# -*- coding: utf-8 -*-
"""
Импорт ленты истории города Малмыж (Вятушка/города/) в WordPress
(testsite.vyatgeo.ru) через REST API плагина history. По образцу
import_history_urzhum_yaransk.py, с поправками под особенности именно
этого источника (см. докстроку ниже) — отчёт по аномалиям см. в чате
агента, кратко продублирован здесь.

Использование:
    python import_history_malmyzh.py --dry-run     # план без записи
    python import_history_malmyzh.py --limit 3       # тестовый прогон
    python import_history_malmyzh.py                  # полный импорт (100 событий)

Отличия и находки по сравнению с Уржумом/Яранском:
  - Термин таксономии city «Малмыж» (id=429) УЖЕ существует на сервере
    И у него УЖЕ заполнены city_coord ('56.52, 50.68') и city_photo
    (id=22687, реальное аэрофото города с Богоявленским собором — не
    плейсхолдер) — в отличие от Уржума/Яранска, где оба поля были
    пустыми. Скрипт их НЕ перезаписывает, только проверяет и пропускает,
    если уже заполнено (тот же принцип гварда, что и в history.php для
    city_coord Кирова — "не перезаписывать уже заданное").
  - 16 из 100 строк источника (номера 8, 22, 23, 46, 49, 68, 70, 75, 76,
    83, 85, 89, 90, 95, 97, 100) не содержат отдельного текста события —
    колонка «текст» пуста, а вся связная фраза с датой внутри лежит в
    «заголовке» (например, строка 22: «В 1861 году в Малмыже заработал
    телеграф — несколько раньше, чем в Вятке...»), в отличие от Уржума/
    Яранска, где заголовок всегда был короткой фразой, а текст — отдельным
    абзацем. Решение: не выдумывать короткий заголовок и не обрезать
    текст — `title` берётся как есть (даже если это полное предложение),
    `content` в этом случае состоит только из фото, без текстового
    абзаца. Отмечено в dry-run отдельным предупреждением на каждую такую
    строку — проверить глазами перед реальной записью.
  - Два значения даты вообще без цифр («Начало XIX в.», «Конец XVI –
    XVII вв.») не ловились старым парсером (он матчил римские цифрby
    только когда они стоят в самом начале строки, без «Начало/Середина/
    Конец» перед ними) — добавлена отдельная ветка с якорями «начало»
    (+10 лет от начала века), «середина» (+50, как раньше для голого
    «XVI в.»), «конец» (+90); для диапазона веков берётся первый.
  - Среди файлов фото встретился 1 `.gif` (98.gif) — добавлен в
    CONTENT_TYPES (в скрипте для Уржума/Яранска такого типа не было).
  - Дублей заголовков и дублей использования номера картинки в разных
    строках (как «75» у Яранска) не найдено — повторных случаев
    страховки (XXX_STRIP_PHOTO) не требуется.
  - Все номера картинок из таблицы (149 уникальных) покрыты файлами без
    пропусков; 2 файла (56.2, 87.2) не упомянуты ни в одной строке — не
    блокер, как и аналогичные случаи у Уржума/Яранска.

Важно: пост создаётся ОДНИМ REST-запросом со всеми мета-полями/таксономией/
featured_media сразу — history.php уже безопасен для REST.
"""

import argparse
import glob
import os
import re
import sys
import html as html_mod

import docx
import pandas as pd
import requests

WORDPRESS_URL = 'https://testsite.vyatgeo.ru/wp-json/'
USERNAME = 'admin'
PASSWORD = 'j12M2e2qN3faOUTDGalfrYKW'

BASE = os.path.join('..', 'Вятушка', 'города')

CITY_TERM_ID = 429
EXCEL = os.path.join(BASE, 'Malmyzh_daty.xlsx')
PHOTO_DIR = os.path.join(BASE, 'Малмыж')
CAPTIONS_DOCX = os.path.join(BASE, 'Malmyzh_izobrazhenia.docx')
# Уже заполнены на сервере реальными данными — скрипт их не перезаписывает,
# значения здесь только для информации/сверки в dry-run.
EXPECTED_COORD = '56.52, 50.68'

# Строки без отдельного «текста» — заголовок сам по себе полное предложение
# с датой (см. докстроку). Не ошибка парсинга, реальная особенность источника.
EMPTY_TEXT_ROW_NUMBERS = {8, 22, 23, 46, 49, 68, 70, 75, 76, 83, 85, 89, 90, 95, 97, 100}

MONTHS = {
    'январ': 1, 'феврал': 2, 'март': 3, 'апрел': 4, 'ма': 5, 'июн': 6,
    'июл': 7, 'август': 8, 'сентябр': 9, 'октябр': 10, 'ноябр': 11, 'декабр': 12,
}
SEASON_MONTH = {'зим': 1, 'весн': 3, 'лет': 6, 'осен': 9}
ROMAN = {'XIV': 14, 'XV': 15, 'XVI': 16, 'XVII': 17, 'XVIII': 18, 'XIX': 19, 'XX': 20}
CENTURY_PART_OFFSET = {'начало': 10, 'середина': 50, 'конец': 90}

session = requests.Session()
session.auth = (USERNAME, PASSWORD)

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
    """Текст источника -> ISO-дата (Y-m-d) для history_date_value. Правила —
    см. докстроку модуля (отличия от Уржума/Яранска — ветка «Начало/
    Середина/Конец {век}»)."""
    s = clean(raw)
    if not s:
        return None

    # «Начало/Середина/Конец XVI в.» и диапазоны веков «Конец XVI – XVII вв.»
    # — римские цифры не обязательно в самом начале строки.
    m = re.search(
        r'(начало|середина|конец)\s+(XIV|XV|XVI|XVII|XVIII|XIX|XX)'
        r'(?:\s*[–—-]\s*(XIV|XV|XVI|XVII|XVIII|XIX|XX))?\s*вв?\.?',
        s, re.IGNORECASE,
    )
    if m:
        part = m.group(1).lower()
        century = ROMAN[m.group(2).upper()]
        offset = CENTURY_PART_OFFSET[part]
        year = (century - 1) * 100 + offset
        return f'{year:04d}-01-01'

    # Голый век римскими цифрами в начале строки: середина века.
    m = re.match(r'^(XIV|XV|XVI|XVII|XVIII|XIX|XX)\s*в\.?$', s)
    if m:
        century = ROMAN[m.group(1)]
        mid_year = (century - 1) * 100 + 50
        return f'{mid_year:04d}-01-01'

    # Диапазон/список лет-десятилетий: "1890-е", "1680-е г."
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

    # Месяц без дня + год ("Январь 1918 г.", "Ноябрь 1920 г.").
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

    # Просто год (первый упомянутый), включая диапазоны вида "1918 - 1919 гг.".
    m = re.search(r'(\d{4})', s)
    if m:
        return f'{int(m.group(1)):04d}-01-01'

    return None


def parse_captions(docx_path):
    """{'17': 'Герб Уржума', '9.1': '...'} — построчный разбор докстроки с
    подписями. Эвристика — при неполном совпадении подпись остаётся пустой,
    это не блокирует импорт."""
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


CONTENT_TYPES = {
    'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png',
    'webp': 'image/webp', 'gif': 'image/gif',
}


def upload_photo(number, photo_index):
    cache_key = number
    if cache_key in _media_cache:
        return _media_cache[cache_key]
    if DRY_RUN:
        _media_cache[cache_key] = (DRY_RUN_MEDIA_ID, f'dry-run://malmyzh/{number}')
        return _media_cache[cache_key]

    path = photo_index.get(number)
    if not path:
        log(f'  ! нет файла для номера {number}')
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

def ensure_city_meta():
    log(f'=== Проверка city_coord/city_photo города «Малмыж» (id={CITY_TERM_ID}) ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/city/{CITY_TERM_ID}', params={'context': 'edit'})
    if resp.status_code != 200:
        log(f'  ! не удалось получить термин города: {resp.status_code}')
        return
    meta = resp.json().get('meta', {})
    coord = meta.get('city_coord', '')
    photo = meta.get('city_photo', 0)
    if coord and photo:
        log(f'  уже заполнено: city_coord={coord!r}, city_photo={photo!r} — пропуск, не перезаписываю')
        if coord != EXPECTED_COORD:
            log(f'  ! обратите внимание: отличается от ожидаемого {EXPECTED_COORD!r} — проверить вручную')
    else:
        log(f'  ! city_coord={coord!r} city_photo={photo!r} — что-то не заполнено, '
            f'заполнение не входит в этот скрипт (не ожидалось, что понадобится), обновить вручную в wp-admin')


# --------------------------------------------------------------- content ----

def build_event_content(text, numbers, photo_index, captions):
    parts = [f'<p>{text}</p>'] if text else []
    featured_media = None
    for number in numbers:
        media = upload_photo(number, photo_index)
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


def import_events(limit=None, dry_run=False):
    log('\n=== История: Малмыж ===')
    df = pd.read_excel(EXCEL)
    rows = list(enumerate(df.to_dict('records'), 1))  # (номер строки, row)
    if limit:
        rows = rows[:limit]

    photo_index = build_photo_index(PHOTO_DIR)
    captions = parse_captions(CAPTIONS_DOCX)

    log(f'К обработке: {len(rows)}')

    created = skipped = errors = empty_text_warnings = 0
    for i, (row_num, row) in enumerate(rows, 1):
        title = clean(row.get('заголовок'))
        text = clean(row.get('текст'))
        date_raw = clean(row.get('дата'))
        numbers_raw = clean(row.get('номер картинки'))
        numbers = [n.strip() for n in re.split(r'[,;]', numbers_raw) if n.strip()]

        if not title:
            continue

        if row_num in EMPTY_TEXT_ROW_NUMBERS and not text:
            empty_text_warnings += 1
            log(f'  ! строка {row_num}: нет отдельного текста, заголовок используется как есть '
                f'({len(title)} симв.): {title!r}')

        content, featured_media = build_event_content(text, numbers, photo_index, captions)
        date_value = parse_history_date(date_raw)

        payload = {
            'title': title,
            'content': content,
            'status': 'publish',
            'history_date_text': date_raw,
            'city': [CITY_TERM_ID],
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
            log(f'[{i}/{len(rows)}] DRY-RUN: {title[:70]!r} (дата={date_raw!r} -> {date_value}, '
                f'фото={numbers})')
            continue

        try:
            result = create_post('history', payload)
            log(f'[{i}/{len(rows)}] создан id={result["id"]}: {title[:70]}')
            created += 1
        except Exception as e:
            log(f'[{i}/{len(rows)}] ОШИБКА: {title[:70]}: {e}')
            errors += 1

    log(f'\nМалмыж — создано: {created}, пропущено: {skipped}, ошибок: {errors}, '
        f'строк без отдельного текста: {empty_text_warnings}')
    return created, skipped, errors


# ------------------------------------------------------------------ main --

def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--limit', type=int, default=None, help='ограничить число событий (для теста)')
    parser.add_argument('--dry-run', action='store_true', help='ничего не создавать, только показать план')
    args = parser.parse_args()

    global DRY_RUN
    DRY_RUN = args.dry_run

    log('=== Проверка подключения к WordPress ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/users/me')
    resp.raise_for_status()
    log(f'Авторизован как: {resp.json().get("name")}')

    if not args.dry_run:
        confirm = input(f'Начать импорт (limit={args.limit or "все 100"})? (y/n): ').strip().lower()
        if confirm not in ('y', 'yes', 'д', 'да'):
            log('Отменено пользователем.')
            sys.exit(0)

    ensure_city_meta()
    import_events(limit=args.limit, dry_run=args.dry_run)


if __name__ == '__main__':
    main()
