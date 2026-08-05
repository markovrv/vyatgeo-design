#!/usr/bin/env python3
"""
Загрузка результатов автоклассификации (classification_result.json) на сервер:
проставляет термин таксономии finding_type для каждой находки через кастомный
REST-эндпоинт findings/v1/findings/{id} (см. api/artifact-finder/rest-api.php).

Основано на паттерне D:\\Desktop\\Проекты\\wp\\finding\\import.py (auth, REST-вызовы),
но:
  - учётные данные читаются из переменных окружения (WP_USERNAME/WP_PASSWORD),
    а не хранятся в исходном коде;
  - термины НЕ создаются на лету (get_or_create) — используется жёсткая карта
    имя -> ID, полученная с сервера, чтобы опечатка в label никогда не создала
    случайный 11-й термин;
  - по умолчанию dry-run (ничего не пишет), запись — только с --apply;
  - есть --ids / --limit для точечного теста перед массовым прогоном;
  - checkpoint (JSONL) для устойчивости к обрыву на большом прогоне.
"""

import argparse
import json
import os
import sys
import time
from pathlib import Path
from typing import Dict, List, Optional

import requests

WORDPRESS_URL = "https://testsite.vyatgeo.ru/wp-json/wp/v2/"
CUSTOM_API_URL = "https://testsite.vyatgeo.ru/wp-json/findings/v1/findings/"
CLASSIFICATION_FILE = "classification_result.json"
CHECKPOINT_FILE = "upload_checkpoint.jsonl"

MAX_RETRIES = 5
BASE_DELAY = 2.0
REQUEST_DELAY = 0.3  # пауза между запросами, чтобы не долбить сервер


def get_auth() -> tuple:
    user = os.environ.get("WP_USERNAME")
    password = os.environ.get("WP_PASSWORD")
    if not user or not password:
        print(
            "ОШИБКА: задайте переменные окружения WP_USERNAME и WP_PASSWORD.",
            file=sys.stderr,
        )
        sys.exit(1)
    return (user, password)


def http_with_retry(method: str, url: str, auth: tuple, **kwargs) -> requests.Response:
    for attempt in range(MAX_RETRIES):
        try:
            resp = requests.request(method, url, auth=auth, timeout=30, **kwargs)
            if resp.status_code >= 500 or resp.status_code == 429:
                raise requests.exceptions.HTTPError(f"HTTP {resp.status_code}")
            return resp
        except (requests.exceptions.RequestException,) as e:
            if attempt == MAX_RETRIES - 1:
                raise
            delay = BASE_DELAY * (2 ** attempt)
            print(f"  [retry] {e}, попытка {attempt+1}/{MAX_RETRIES}, ждём {delay:.0f}с...")
            time.sleep(delay)
    raise RuntimeError("unreachable")


def fetch_finding_type_terms(auth: tuple) -> Dict[str, int]:
    """Скачать существующие термины finding_type -> {имя: id}. Ничего не создаёт."""
    resp = http_with_retry(
        "GET", f"{WORDPRESS_URL}finding_type", auth, params={"per_page": 100}
    )
    resp.raise_for_status()
    terms = resp.json()
    return {t["name"]: t["id"] for t in terms}


def fetch_finding_snapshot(finding_id: int, auth: tuple) -> Optional[dict]:
    """Полный снимок записи (для верификации до/после)."""
    resp = http_with_retry(
        "GET", f"{WORDPRESS_URL}finding/{finding_id}", auth,
        params={"context": "edit"},
    )
    if resp.status_code != 200:
        return None
    return resp.json()


def apply_finding_type(finding_id: int, term_id: int, auth: tuple) -> dict:
    """POST на кастомный endpoint — ставит ТОЛЬКО finding_type, остальное не трогает."""
    body = {
        "taxonomies": {"finding_type": [term_id]},
        "meta_fields": {},  # обязателен по схеме endpoint'а, но пуст — ничего не меняет
    }
    resp = http_with_retry(
        "POST", f"{CUSTOM_API_URL}{finding_id}", auth, json=body
    )
    return {"status_code": resp.status_code, "body": _safe_json(resp)}


def _safe_json(resp: requests.Response):
    try:
        return resp.json()
    except ValueError:
        return resp.text[:500]


def load_classification(path: str) -> List[dict]:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def load_checkpoint(path: Path) -> set:
    done = set()
    if not path.exists():
        return done
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            try:
                rec = json.loads(line)
                if rec.get("ok"):
                    done.add(rec["id"])
            except json.JSONDecodeError:
                pass
    return done


def append_checkpoint(path: Path, record: dict):
    with open(path, "a", encoding="utf-8") as f:
        f.write(json.dumps(record, ensure_ascii=False) + "\n")


def main():
    parser = argparse.ArgumentParser(
        description="Загрузка finding_type из classification_result.json на сервер"
    )
    parser.add_argument("--input", default=CLASSIFICATION_FILE)
    parser.add_argument("--checkpoint", default=CHECKPOINT_FILE)
    parser.add_argument("--apply", action="store_true", help="Реально писать на сервер (без флага — dry-run)")
    parser.add_argument("--ids", default=None, help="Обработать только эти ID через запятую (для теста)")
    parser.add_argument("--limit", type=int, default=None, help="Обработать только первые N записей")
    parser.add_argument("--verify", action="store_true", help="После записи сверить finding_type через GET")
    args = parser.parse_args()

    auth = get_auth()

    print("Загрузка classification_result.json ...")
    records = load_classification(args.input)
    print(f"  Всего записей в файле: {len(records)}")

    records = [r for r in records if r.get("final_label")]
    print(f"  С непустым final_label: {len(records)}")

    if args.ids:
        wanted = {int(x) for x in args.ids.split(",")}
        records = [r for r in records if r["id"] in wanted]
        print(f"  Отфильтровано по --ids: {len(records)}")

    if args.limit:
        records = records[: args.limit]
        print(f"  Ограничено --limit: {len(records)}")

    print("\nЗапрос действующих терминов finding_type с сервера ...")
    term_map = fetch_finding_type_terms(auth)
    print(f"  Найдено терминов: {len(term_map)}")
    for name, tid in term_map.items():
        print(f"    {tid}: {name}")

    # Сверка: все ли метки из classification_result.json есть среди терминов сервера
    labels_in_data = {r["final_label"] for r in records}
    unknown_labels = labels_in_data - set(term_map.keys())
    if unknown_labels:
        print(f"\nОШИБКА: метки без соответствующего термина на сервере: {unknown_labels}")
        print("Термины НЕ будут создаваться автоматически. Прерываю выполнение.")
        sys.exit(1)

    checkpoint_path = Path(args.checkpoint)
    done_ids = load_checkpoint(checkpoint_path)
    if done_ids:
        print(f"\nЧекпоинт: уже загружено ранее {len(done_ids)} записей, пропускаю их.")

    todo = [r for r in records if r["id"] not in done_ids]
    print(f"\nК обработке: {len(todo)} записей. Режим: {'ЗАПИСЬ НА СЕРВЕР' if args.apply else 'DRY-RUN (ничего не меняю)'}")

    ok_count = 0
    fail_count = 0

    for i, rec in enumerate(todo, 1):
        fid = rec["id"]
        label = rec["final_label"]
        term_id = term_map[label]

        print(f"[{i}/{len(todo)}] id={fid} title={rec.get('title','')!r} -> {label} (term {term_id})")

        if not args.apply:
            continue

        before = fetch_finding_snapshot(fid, auth) if args.verify else None

        result = apply_finding_type(fid, term_id, auth)
        success = result["status_code"] == 200 and isinstance(result["body"], dict) and result["body"].get("success")

        if success:
            ok_count += 1
            append_checkpoint(checkpoint_path, {"id": fid, "label": label, "ok": True})
        else:
            fail_count += 1
            print(f"    ОШИБКА: {result}")
            append_checkpoint(checkpoint_path, {"id": fid, "label": label, "ok": False, "error": result})

        if args.verify and success:
            after = fetch_finding_snapshot(fid, auth)
            after_types = after.get("finding_type", []) if after else []
            if after_types != [term_id]:
                print(f"    ПРЕДУПРЕЖДЕНИЕ: после записи finding_type={after_types}, ожидался [{term_id}]")
            else:
                print(f"    OK, проверено: finding_type={after_types}")
            # Сверяем, что остальные поля не изменились (кроме finding_type)
            if before:
                diffs = []
                for key in ("title", "content", "finding_material", "finding_origin",
                            "finding_receipt_time", "finding_creation_time",
                            "finding_dimensions", "finding_cat_id", "finding_functionality",
                            "finding_features"):
                    b = before.get(key)
                    a = after.get(key) if after else None
                    if b != a:
                        diffs.append(key)
                if diffs:
                    print(f"    ПРЕДУПРЕЖДЕНИЕ: изменились посторонние поля: {diffs}")
                else:
                    print(f"    OK, посторонние поля не тронуты")

        time.sleep(REQUEST_DELAY)

    print("\n" + "=" * 50)
    print(f"Готово. Успешно: {ok_count}, ошибок: {fail_count}, пропущено (уже было): {len(records) - len(todo)}")


if __name__ == "__main__":
    main()
