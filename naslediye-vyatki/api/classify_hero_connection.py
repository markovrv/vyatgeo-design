# -*- coding: utf-8 -*-
"""
Классификация свободного текста hero_connection («Характер связи с Вятским
краем») по 9 категориям и запись в новую таксономию hero_connection_type
(WordPress testsite.vyatgeo.ru, CPT hero). Решения по схеме — см. план,
согласованный с заказчиком (категория «Увековечен» — в той же таксономии,
не отдельно; «Служил»/«Воевал» — один термин, не разделяются).

Текстовое поле hero_connection НЕ трогается и НЕ удаляется — таксономия
это дополнительный слой для фильтрации поверх него, как и везде на сайте.

Правила — regex по ключевым словам, подобранным на реальных 345 записях
(не придуманы заранее), с учётом найденных опечаток источника («Кроженец»
вместо «Уроженец», «провл» вместо «провёл»). «Учился» намеренно не ловит
«мемориальная доска в школе №…» — это про саму доску, не про учёбу героя
(см. функцию classify, категория studied использует только «учился в/на»,
а не слово «школа» само по себе).

Использование:
    python classify_hero_connection.py --dry-run      # план + таблица частот, без записи
    python classify_hero_connection.py --limit 5        # тестовый прогон
    python classify_hero_connection.py                  # полный прогон (345 записей)

Перед реальной записью сохраняется лог классификации
(hero_connection_classify_log.json) — id, исходный текст, присвоенные категории.
"""

import argparse
import json
import re
import sys

import requests

WORDPRESS_URL = 'https://testsite.vyatgeo.ru/wp-json/'
USERNAME = 'admin'
PASSWORD = 'j12M2e2qN3faOUTDGalfrYKW'

LOG_FILE = 'hero_connection_classify_log.json'

# Порядок — он же порядок создания терминов при первом прогоне.
CATEGORIES = [
    ('born', 'Родился в крае'),
    ('lived', 'Жил / детство и юность'),
    ('studied', 'Учился'),
    ('worked', 'Работал'),
    ('served', 'Служил / воевал'),
    ('drafted', 'Призван отсюда'),
    ('died', 'Умер'),
    ('buried', 'Похоронен'),
    ('memorialized', 'Увековечен в крае'),
]

session = requests.Session()
session.auth = (USERNAME, PASSWORD)

_term_cache = {}
DRY_RUN = False
DRY_RUN_TERM_ID = -1


def log(msg):
    print(msg, flush=True)


def classify(raw):
    """Текст hero_connection -> список ключей категорий (0..N)."""
    s = (raw or '').lower()
    if not s.strip():
        return []

    hits = set()

    # --- born: «родился», «уроженец/уроженка», опечатка «кроженец» ---
    if re.search(r'родил|урожен|кроженец', s):
        hits.add('born')

    # --- lived: «жил», «житель», «проживал», «детство/юность» ---
    if re.search(r'\bжил|жител|проживал|детств|юност', s):
        hits.add('lived')

    # --- studied: «учился»/«училась» в любом месте фразы (не «школа» сама
    # по себе — «мемориальная доска в школе №…» не должна матчиться, но она
    # и не матчится: триггер — глагол «учился», а не слово «школа») ---
    if re.search(r'учился|училась', s):
        hits.add('studied')

    # --- worked ---
    if re.search(r'работал|работала', s):
        hits.add('worked')

    # --- served: служил/воевал (военная служба на территории края) ---
    if re.search(r'служил|служила|воева', s):
        hits.add('served')

    # --- drafted: «призван» и «призыв» — разные корни («призв-ан» vs
    # «призыв-а», гласная «ы» не выпадает), нужны оба варианта ---
    if re.search(r'призв|призыв', s):
        hits.add('drafted')

    # --- died ---
    if re.search(r'умер|сконча', s):
        hits.add('died')

    # --- buried ---
    if re.search(r'похорон', s):
        hits.add('buried')

    # --- memorialized: стела/улица/доска/бюст/почётный гражданин ---
    if re.search(r'стел|улиц|мемориальн|бюст|почётный гражданин|почетный гражданин|имя выбито|имя увековечено', s):
        hits.add('memorialized')

    return [key for key, _ in CATEGORIES if key in hits]


def fetch_all_heroes():
    all_heroes = []
    page = 1
    while True:
        resp = session.get(
            f'{WORDPRESS_URL}wp/v2/hero',
            params={'per_page': 100, 'page': page, '_fields': 'id,title,hero_connection'},
        )
        resp.raise_for_status()
        data = resp.json()
        if not data:
            break
        all_heroes.extend(data)
        total_pages = int(resp.headers.get('X-WP-TotalPages', '1'))
        if page >= total_pages:
            break
        page += 1
    return all_heroes


def get_or_create_term(name):
    key = name.lower()
    if key in _term_cache:
        return _term_cache[key]
    if DRY_RUN:
        return DRY_RUN_TERM_ID

    resp = session.get(f'{WORDPRESS_URL}wp/v2/hero_connection_type', params={'search': name, 'per_page': 100})
    resp.raise_for_status()
    for term in resp.json():
        if term['name'].strip().lower() == key:
            _term_cache[key] = term['id']
            return term['id']

    resp = session.post(f'{WORDPRESS_URL}wp/v2/hero_connection_type', json={'name': name})
    if resp.status_code not in (200, 201):
        log(f'  ! не удалось создать термин {name!r} — {resp.status_code} {resp.text[:200]}')
        return None
    term_id = resp.json()['id']
    _term_cache[key] = term_id
    return term_id


def update_hero_terms(hero_id, term_ids):
    resp = session.post(f'{WORDPRESS_URL}wp/v2/hero/{hero_id}', json={'hero_connection_type': term_ids})
    if resp.status_code not in (200, 201):
        raise RuntimeError(f'HTTP {resp.status_code}: {resp.text[:300]}')
    return resp.json()


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--limit', type=int, default=None, help='ограничить число записей (для теста)')
    parser.add_argument('--dry-run', action='store_true', help='ничего не писать, только план + статистика')
    args = parser.parse_args()

    global DRY_RUN
    DRY_RUN = args.dry_run

    log('=== Проверка подключения к WordPress ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/users/me')
    resp.raise_for_status()
    log(f'Авторизован как: {resp.json().get("name")}')

    log('\n=== Классификация hero_connection ===')
    heroes = fetch_all_heroes()
    if args.limit:
        heroes = heroes[:args.limit]

    import html as html_mod
    results = []
    freq = {key: 0 for key, _ in CATEGORIES}
    zero_match = []
    for h in heroes:
        title = html_mod.unescape(h.get('title', {}).get('rendered', ''))
        raw = h.get('hero_connection', '')
        cats = classify(raw)
        for c in cats:
            freq[c] += 1
        if not cats and raw.strip():
            zero_match.append((h['id'], title, raw))
        results.append({'id': h['id'], 'title': title, 'raw': raw, 'categories': cats})

    with open(LOG_FILE, 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    log(f'Лог классификации записан в {LOG_FILE} ({len(results)} записей)')

    log('\n--- Частоты категорий ---')
    for key, label in CATEGORIES:
        log(f'  {label}: {freq[key]}')

    log(f'\n--- Не распознано ни одной категории, но текст не пуст: {len(zero_match)} ---')
    for hid, title, raw in zero_match[:20]:
        log(f'  id={hid} {title!r}: {raw!r}')
    if len(zero_match) > 20:
        log(f'  ... и ещё {len(zero_match) - 20}')

    if args.dry_run:
        log(f'\nDRY-RUN: всего было бы обновлено {len(results)} записей (термины создаются по мере необходимости).')
        return

    confirm = input(f'Записать категории на {len(results)} героев? (y/n): ').strip().lower()
    if confirm not in ('y', 'yes', 'д', 'да'):
        log('Отменено пользователем.')
        sys.exit(0)

    updated = errors = 0
    for i, item in enumerate(results, 1):
        term_ids = [tid for tid in (get_or_create_term(dict(CATEGORIES)[c]) for c in item['categories']) if tid]
        try:
            update_hero_terms(item['id'], term_ids)
            log(f'[{i}/{len(results)}] id={item["id"]} {item["title"]!r}: {item["categories"]}')
            updated += 1
        except Exception as e:
            log(f'[{i}/{len(results)}] ОШИБКА id={item["id"]}: {e}')
            errors += 1

    log(f'\nГотово — обновлено: {updated}, ошибок: {errors}')


if __name__ == '__main__':
    main()
