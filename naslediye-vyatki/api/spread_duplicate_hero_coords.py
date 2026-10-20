# -*- coding: utf-8 -*-
"""
Раздвигает героев с полностью одинаковыми hero_coord (WordPress
testsite.vyatgeo.ru, CPT hero) на небольшое расстояние друг от друга.

Проблема: 271 из 345 героев делят координаты ещё с кем-то (самая массовая
группа — 61 герой на точке '58.6, 49.6'). Яндекс-кластеризатор на карте
(src/components/HeroMap.vue, gridSize=128, maxZoom=18) группирует такие
точки в один кластер, который не разворачивается в отдельные пины сколько
ни приближай карту — герои внутри физически не видны и не кликабельны.

Решение: для каждой группы героев с одинаковым hero_coord раскладываем их
по окружности вокруг исходной точки, радиус растёт с размером группы
(0.15 * sqrt(N) км, не меньше 250 м) — этого достаточно, чтобы кластер
разворачивался на разумном уровне приближения, и герои остаются в той же
местности (это не более точные координаты, а способ сделать каждого героя
кликабельным на карте).

Использование:
    python spread_duplicate_hero_coords.py --dry-run   # план без записи, лог в JSON
    python spread_duplicate_hero_coords.py --limit 1    # тест: раздвинуть только первую группу
    python spread_duplicate_hero_coords.py               # применить ко всем дублирующимся группам

Перед реальной записью сохраняется лог id/старая-координата/новая-координата
(hero_coord_spread_log.json рядом со скриптом) — на случай если понадобится
свериться или отменить.
"""

import argparse
import json
import math
import sys
from collections import defaultdict

import requests

WORDPRESS_URL = 'https://testsite.vyatgeo.ru/wp-json/'
USERNAME = 'admin'
PASSWORD = 'j12M2e2qN3faOUTDGalfrYKW'

EARTH_KM_PER_DEG_LAT = 111.32
MIN_RADIUS_KM = 0.25
RADIUS_PER_SQRT_N = 0.15

LOG_FILE = 'hero_coord_spread_log.json'

session = requests.Session()
session.auth = (USERNAME, PASSWORD)


def log(msg):
    print(msg, flush=True)


def fetch_all_heroes():
    all_heroes = []
    page = 1
    while True:
        resp = session.get(
            f'{WORDPRESS_URL}wp/v2/hero',
            params={'per_page': 100, 'page': page, '_fields': 'id,title,hero_coord'},
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


def parse_coord(raw):
    parts = [p.strip() for p in (raw or '').split(',') if p.strip()]
    if len(parts) != 2:
        return None
    try:
        return float(parts[0]), float(parts[1])
    except ValueError:
        return None


def jitter_points(base_lat, base_lng, n):
    """N точек по окружности вокруг (base_lat, base_lng), радиус растёт с N."""
    if n <= 1:
        return [(round(base_lat, 6), round(base_lng, 6))]
    radius_km = max(MIN_RADIUS_KM, RADIUS_PER_SQRT_N * math.sqrt(n))
    km_per_deg_lng = EARTH_KM_PER_DEG_LAT * math.cos(math.radians(base_lat)) or EARTH_KM_PER_DEG_LAT
    points = []
    for i in range(n):
        angle = 2 * math.pi * i / n
        dlat = (radius_km * math.cos(angle)) / EARTH_KM_PER_DEG_LAT
        dlng = (radius_km * math.sin(angle)) / km_per_deg_lng
        points.append((round(base_lat + dlat, 6), round(base_lng + dlng, 6)))
    return points


def build_plan():
    heroes = fetch_all_heroes()
    groups = defaultdict(list)
    for h in heroes:
        coord = (h.get('hero_coord') or '').strip()
        if coord:
            groups[coord].append(h)

    plan = []  # [(id, title, old_coord, new_coord_str), ...]
    for coord, members in groups.items():
        if len(members) < 2:
            continue
        base = parse_coord(coord)
        if base is None:
            continue
        base_lat, base_lng = base
        members_sorted = sorted(members, key=lambda h: h['id'])
        points = jitter_points(base_lat, base_lng, len(members_sorted))
        for hero, (lat, lng) in zip(members_sorted, points):
            title = hero.get('title', {})
            title_text = title.get('rendered', '') if isinstance(title, dict) else str(title)
            plan.append({
                'id': hero['id'],
                'title': title_text,
                'old_coord': coord,
                'new_coord': f'{lat}, {lng}',
                'group_size': len(members_sorted),
            })
    return plan


def update_hero_coord(hero_id, new_coord):
    resp = session.post(f'{WORDPRESS_URL}wp/v2/hero/{hero_id}', json={'hero_coord': new_coord})
    if resp.status_code not in (200, 201):
        raise RuntimeError(f'HTTP {resp.status_code}: {resp.text[:300]}')
    return resp.json()


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--limit', type=int, default=None, help='ограничить число ГРУПП дублей (для теста)')
    parser.add_argument('--dry-run', action='store_true', help='ничего не менять, только показать план и записать лог')
    args = parser.parse_args()

    log('=== Проверка подключения к WordPress ===')
    resp = session.get(f'{WORDPRESS_URL}wp/v2/users/me')
    resp.raise_for_status()
    log(f'Авторизован как: {resp.json().get("name")}')

    log('\n=== Построение плана (группировка по hero_coord) ===')
    plan = build_plan()

    # Группируем план обратно по old_coord только для отчёта/лимита по группам.
    by_group = defaultdict(list)
    for item in plan:
        by_group[item['old_coord']].append(item)
    groups_sorted = sorted(by_group.items(), key=lambda kv: -len(kv[1]))
    if args.limit:
        groups_sorted = groups_sorted[:args.limit]
    limited_plan = [item for _, items in groups_sorted for item in items]

    log(f'Групп с дублями: {len(by_group)} (после --limit: {len(groups_sorted)}), '
        f'героев к раздвижке: {len(limited_plan)}')

    with open(LOG_FILE, 'w', encoding='utf-8') as f:
        json.dump(limited_plan, f, ensure_ascii=False, indent=2)
    log(f'План/лог записан в {LOG_FILE}')

    if args.dry_run:
        for coord, items in groups_sorted[:5]:
            log(f'\nDRY-RUN группа {coord!r} ({len(items)} героев):')
            for item in items[:3]:
                log(f'  id={item["id"]} {item["title"]!r}: {item["old_coord"]} -> {item["new_coord"]}')
            if len(items) > 3:
                log(f'  ... и ещё {len(items) - 3}')
        log(f'\nDRY-RUN: всего было бы обновлено {len(limited_plan)} записей.')
        return

    confirm = input(f'Обновить hero_coord у {len(limited_plan)} героев ({len(groups_sorted)} групп)? (y/n): ').strip().lower()
    if confirm not in ('y', 'yes', 'д', 'да'):
        log('Отменено пользователем.')
        sys.exit(0)

    updated = errors = 0
    for i, item in enumerate(limited_plan, 1):
        try:
            update_hero_coord(item['id'], item['new_coord'])
            log(f'[{i}/{len(limited_plan)}] id={item["id"]} {item["title"]!r}: '
                f'{item["old_coord"]} -> {item["new_coord"]}')
            updated += 1
        except Exception as e:
            log(f'[{i}/{len(limited_plan)}] ОШИБКА id={item["id"]}: {e}')
            errors += 1

    log(f'\nГотово — обновлено: {updated}, ошибок: {errors}')


if __name__ == '__main__':
    main()
