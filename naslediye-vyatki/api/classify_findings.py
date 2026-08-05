#!/usr/bin/env python3
"""
Скрипт автоклассификации коллекции «Этнография» по таксономии finding_type.

Два прохода:
  1) Rule-based — быстрое сопоставление по словарям ключевых слов.
  2) LLM (DeepSeek) — подтверждение/арбитраж для спорных случаев.

Результат пишется в локальный JSON-файл (не в WordPress).
Поддерживает докачку (checkpoint) и устойчив к обрывам сети.
"""

import argparse
import json
import os
import sys
import time
import re
from collections import OrderedDict
from pathlib import Path
from typing import Dict, List, Optional, Tuple, Any

import requests

# ---------------------------------------------------------------------------
# Конфигурация по умолчанию
# ---------------------------------------------------------------------------
DEFAULT_BASE_URL = "https://testsite.vyatgeo.ru/wp-json/findings/v1/findings/"
DEFAULT_PER_PAGE = 50
DEFAULT_OUTPUT = "classification_result.json"
DEFAULT_MODEL = "deepseek-v4-flash"
DEFAULT_BATCH_SIZE = 15
MAX_RETRIES = 5
BASE_DELAY = 2.0          # секунды, для экспоненциального backoff

DEEPSEEK_CHAT_URL = "https://api.deepseek.com/v1/chat/completions"


# ---------------------------------------------------------------------------
# Категории (ровно 10) и их словари ключевых слов / стем
# Каждый список включает базовые формы (нижний регистр) – сравнение будет
# проводиться по подстрокам без учёта регистра.
# ---------------------------------------------------------------------------
CATEGORIES: Dict[str, str] = OrderedDict([
    ("Костюм", (
        "одежда обувь головной убор шапка платок кокошник сарафан рубаха "
        "порты штаны юбка кофта кафтан зипун армяк тулуп шуба полушубок "
        "передник фартук пояс кушак опояска украшение бусы серьги кольцо "
        "браслет ожерелье гривна венец венчик лента тесьма кружево вышивка "
        "галун позумент лапти валенки сапоги чеботы коты поршни онучи "
        "чулки носки исподница поддёвка душегрея епанча накидка плащ "
        "перчатки рукавицы варежки голицы"
    )),
    ("Посуда", (
        "посуда горшок кринка крынка миска чашка ложка кувшин тарелка блюдо "
        "чаша чарка стакан кружка ковш братина ендова корчага кубышка "
        "жбан кумган лохань ушат ведро кадка бочка бочонок маслобойка "
        "ступка пест тёрка сито решето дуршлаг самовар чайник кофейник "
        "соусник супница селёдочница икорница сахарница молочник "
        "сливочник маслёнка хлебница солонка перечница горчичница "
        "рюмка бокал фужер стопка штоф графин бутыль"
    )),
    ("Мебель", (
        "мебель стол стул лавка скамья сундук полка шкаф кровать диван "
        "тахта кушетка буфет комод тумба этажерка горка сервант секретер "
        "бюро конторка люлька колыбель зыбка кресло табурет столешница "
        "столярный мебельный гарнитур ширма вешалка зеркало трюмо "
        "сундучок укладка коробья скрыня постав рундук"
    )),
    ("Предметы культа", (
        "икона крест образ складень лампада кадило кадильница паникадило "
        "хоругвь дарохранительница дароносица потир дискос аналой "
        "церковный обрядовый религиозный культовый богослужебный "
        "молитва молитвенный священный святой риза оклад венец цата "
        "панагия энколпион мощевик просфора антиминс плащаница "
        "канун панихидный требник евангелие апостол псалтырь "
        "венчальный крестильный крестик нательный благословение "
        "освящение святыня"
    )),
    ("Досуг", (
        "игрушка игра кукла мяч свистулька погремушка трещотка дудка "
        "музыкальный инструмент гармонь гармошка баян балалайка гусли "
        "свирель рожок жалейка бубен колокольчик трещотка шарманка "
        "граммофон патефон пластинка праздник забава развлечение "
        "карусель качели хоровод пляска танец игровой настольный "
        "шахматы шашки домино лото бирюльки кубарь волчок городки "
        "бильбоке серсо кегли жмурки горелки потеха гулянье "
        "масленица святочный карнавал маска"
    )),
    ("Торговля", (
        "торговля весы гиря безмен деньги монета купюра ассигнация "
        "банкнота касса кассовый счёты калькулятор арифмометр "
        "торговый инвентарь прилавок вывеска ценник аршин сажень "
        "мерка мера мерный гирька разновес кошелёк мошна портмоне "
        "бумажник копилка сейф ящик кассовый чека чек вексель "
        "облигация акция лотерея ломбард меняла ростовщик купец "
        "купеческий лавка магазин базар ярмарка торг"
    )),
    ("Промыслы", (
        "промысел ремесло ремесленный гончарный кузнечный плетение "
        "плетёный бондарный кожевенный скорняжный шорный валяльный "
        "красильный набойка резной резьба роспись расписной эмаль "
        "финифть скань зернь чернь чеканка гравировка филигрань "
        "ювелирный жестянка лудильный смолокуренный дегтярный "
        "углежжение смола дёготь скипидар живица вар воск свеча "
        "мыловарение спирт винокурение пивоварение медоварение "
        "квасоварение солод пивной бражный перегонный куб самогон"
    )),
    ("Прядение, ткачество, шитьё", (
        "прядение пряжа прясть прялка веретено прядильный ткачество "
        "ткать ткацкий станок тканый нить нитка бердо челнок "
        "притужальник мотовило вороба воробы сновалка шпулька катушка "
        "шитьё шить швейный игла иголка напёрсток ножницы булавка "
        "шпулька швейная машина зингер вышивка вышивать крестик "
        "гладь мережка кружево коклюшка филейный вязание вязать "
        "крючок спица клубок моток пасма кудель лён шерсть "
        "хлопок конопля посконь сукно холст полотно пестрядь "
        "набилка ремиз"
    )),
    ("Сельско-хозяйственные инструменты", (
        "сельский хозяйственный сельскохозяйственный земледелие "
        "земледельческий пахота пахать плуг соха борона серп коса "
        "косьба косить цеп молотить молотьба молотило веялка "
        "веять зерно мука жернов мельница рушилка крупорушка "
        "сев сеялка сеять жатва жать сноп грабли вилы лопата "
        "мотыга тяпка заступ орало рало косуля сабан окучник "
        "прополка полоть рыхлитель культиватор каток прикатывание "
        "сенокос стог копна воз сноповязалка молотилка"
    )),
    ("Плотницко-столярные инструменты", (
        "плотник плотницкий столяр столярный топор рубанок фуганок "
        "шерхебель долото стамеска пила ножовка лучковая пила "
        "обушковая пила двуручная пила коловорот бурав сверло "
        "напарье дрель струг скобель цинубель рейсмус малка "
        "угольник уровень отвес ватерпас клещи молоток киянка "
        "гвоздодёр клещи кусачки тиски верстак точило оселок "
        "брусок шлифовальный наждак рубка тесать строгать "
        "долбить сверлить пилить шкурить"
    )),
])

# Предварительно разобьём строки в множества слов для быстрого поиска
CATEGORY_KEYWORDS: Dict[str, set] = {
    cat: set(text.lower().split()) for cat, text in CATEGORIES.items()
}


# ---------------------------------------------------------------------------
# Вспомогательные функции
# ---------------------------------------------------------------------------

def build_text(record: dict) -> str:
    """Собрать релевантный текст для rule-based классификации."""
    parts = [
        record.get("title") or "",
        record.get("functionality") or "",
        record.get("features") or "",
        " ".join(record.get("materials") or []),
    ]
    text = " ".join(p for p in parts if p).strip()
    if not text:
        text = record.get("content") or ""
    return text


def rule_classify(record: dict) -> Tuple[Optional[str], Dict[str, int], List[str]]:
    """
    Первый проход: rule-based классификация по словарям ключевых слов.
    Использует нестрогий поиск подстрок (in) для учёта падежей и словоформ.
    Возвращает (label, scores_dict, evidence_list).
    """
    text = build_text(record).lower()
    if not text:
        return None, {}, []

    scores: Dict[str, int] = {}
    evidence: Dict[str, List[str]] = {}

    for cat, kw_set in CATEGORY_KEYWORDS.items():
        matched: List[str] = []
        for kw in kw_set:
            if kw in text:
                matched.append(kw)
        if matched:
            scores[cat] = len(matched)
            evidence[cat] = matched

    if not scores:
        return None, {}, []

    # Сортируем по убыванию score
    sorted_cats = sorted(scores.items(), key=lambda x: x[1], reverse=True)
    top1_cat, top1_score = sorted_cats[0]
    top2_score = sorted_cats[1][1] if len(sorted_cats) > 1 else 0

    # Если уверенная ничья — не берёмся гадать
    if top1_score == top2_score and top1_score > 0:
        return None, scores, evidence.get(top1_cat, [])

    return top1_cat, scores, evidence.get(top1_cat, [])


def make_llm_batch_prompt(batch: List[Tuple[dict, str, List[str]]]) -> str:
    """
    Сформировать промпт для LLM-батча.
    batch: список кортежей (record, rule_label, rule_evidence)
    """
    categories_list = "\n".join(
        f"{i+1}. {cat}"
        for i, (cat, _desc) in enumerate(CATEGORIES.items())
    )

    records_text = ""
    for i, (rec, rl, rev) in enumerate(batch):
        records_text += (
            f"--- Запись {i+1} ---\n"
            f"ID: {rec.get('id', '?')}\n"
            f"Название: {rec.get('title', '')}\n"
            f"Функциональность: {rec.get('functionality', '')}\n"
            f"Особенности: {rec.get('features', '')}\n"
            f"Материалы: {', '.join(rec.get('materials', []))}\n"
            f"Время создания: {', '.join(rec.get('creation_time', []))}\n"
            f"Rule-based метка: {rl or 'не определена'}\n"
            f"Ключевые слова rule-based: {', '.join(rev) if rev else 'нет'}\n\n"
        )

    prompt = (
        "Ты — эксперт по этнографии и музейной классификации. "
        "Ниже даны несколько музейных предметов из коллекции «Этнография» "
        "и предварительная метка типа, определённая по ключевым словам.\n\n"
        "Твоя задача: для КАЖДОЙ записи вернуть ОДНУ из следующих 10 категорий "
        "(или \"unclear\", если данных недостаточно):\n\n"
        f"{categories_list}\n\n"
        "——————\n\n"
        f"{records_text}"
        "——————\n\n"
        "Верни СТРОГО JSON-массив (без маркдаун-обёрток ```json), "
        "где каждый элемент — объект с полями:\n"
        "  - id (число, ID записи)\n"
        "  - label (строка, одна из 10 категорий или \"unclear\")\n"
        "  - reasoning (строка, одно предложение с обоснованием)\n"
        "  - agrees_with_rule (boolean, согласна ли модель с rule-based меткой)\n\n"
        "Пример одного элемента:\n"
        '  {"id": 20622, "label": "Прядение, ткачество, шитьё", '
        '"reasoning": "Прялка — инструмент для прядения нити.", '
        '"agrees_with_rule": true}\n\n'
        "Никакого другого текста не выводи, только JSON-массив."
    )
    return prompt


# ---------------------------------------------------------------------------
# Основной рабочий класс
# ---------------------------------------------------------------------------

class ClassificationRunner:
    """Оркестрирует полный пайплайн классификации."""

    def __init__(
        self,
        base_url: str,
        per_page: int,
        output_path: str,
        model: str,
        batch_size: int,
        limit: Optional[int] = None,
        api_key: Optional[str] = None,
    ):
        self.base_url = base_url.rstrip("/")
        self.per_page = per_page
        self.output_path = Path(output_path)
        self.model = model
        self.batch_size = batch_size
        self.limit = limit
        self.api_key = api_key or os.environ.get("DEEPSEEK_API_KEY", "")

        # Checkpoint
        self.checkpoint_path = self.output_path.with_suffix(".jsonl")
        self.processed_ids: set = set()
        self.results: Dict[int, dict] = {}

    # ------------------------------------------------------------------
    # Сетевые хелперы с retry
    # ------------------------------------------------------------------
    def _http_get(self, url: str, params: dict = None) -> dict:
        """GET-запрос с экспоненциальным backoff."""
        for attempt in range(MAX_RETRIES):
            try:
                resp = requests.get(url, params=params, timeout=30)
                resp.raise_for_status()
                return resp.json()
            except requests.exceptions.RequestException as e:
                if attempt == MAX_RETRIES - 1:
                    raise
                delay = BASE_DELAY * (2 ** attempt)
                print(f"  [retry] HTTP {e}, попытка {attempt+1}/{MAX_RETRIES}, "
                      f"ждём {delay:.0f}с...")
                time.sleep(delay)
        raise RuntimeError("unreachable")

    # ------------------------------------------------------------------
    # Загрузка данных из WP API
    # ------------------------------------------------------------------
    def fetch_all(self) -> List[dict]:
        """Скачать все записи (или до --limit) с WP REST API."""
        print(f"Загрузка данных с {self.base_url} ...")

        # Первый запрос — узнаём число страниц
        first = self._http_get(self.base_url, {"per_page": self.per_page, "page": 1})
        total_pages = first.get("total_pages", 1)
        total = first.get("total", 0)
        print(f"  Всего записей: {total}, страниц: {total_pages}")

        all_records = list(first.get("findings", []))

        for page in range(2, total_pages + 1):
            if self.limit and len(all_records) >= self.limit:
                break
            print(f"  Страница {page}/{total_pages} ...")
            data = self._http_get(
                self.base_url, {"per_page": self.per_page, "page": page}
            )
            all_records.extend(data.get("findings", []))

        if self.limit:
            all_records = all_records[: self.limit]

        print(f"  Загружено записей: {len(all_records)}")
        return all_records

    # ------------------------------------------------------------------
    # Загрузка / сохранение чекпоинта
    # ------------------------------------------------------------------
    def load_checkpoint(self):
        """Загрузить уже обработанные записи из JSONL."""
        if not self.checkpoint_path.exists():
            return
        print(f"Загрузка чекпоинта из {self.checkpoint_path} ...")
        with open(self.checkpoint_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line:
                    continue
                try:
                    rec = json.loads(line)
                    rid = rec.get("id")
                    if rid is not None:
                        self.processed_ids.add(rid)
                        self.results[rid] = rec
                except json.JSONDecodeError:
                    pass
        print(f"  Уже обработано: {len(self.processed_ids)} записей")

    def save_checkpoint(self, result: dict):
        """Дописать одну запись в JSONL (атомарно через append)."""
        with open(self.checkpoint_path, "a", encoding="utf-8") as f:
            f.write(json.dumps(result, ensure_ascii=False) + "\n")
        self.processed_ids.add(result["id"])
        self.results[result["id"]] = result

    # ------------------------------------------------------------------
    # LLM-батч с retry (DeepSeek API)
    # ------------------------------------------------------------------
    def _call_llm(self, prompt: str) -> List[dict]:
        """Вызвать DeepSeek Chat Completions API, вернуть распарсенный JSON-массив."""
        if not self.api_key:
            raise RuntimeError(
                "DEEPSEEK_API_KEY не задан. Установите переменную окружения "
                "или передайте ключ."
            )

        headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json",
        }

        for attempt in range(MAX_RETRIES):
            try:
                resp = requests.post(
                    DEEPSEEK_CHAT_URL,
                    headers=headers,
                    json={
                        "model": self.model,
                        "messages": [
                            {
                                "role": "system",
                                "content": (
                                    "Ты — эксперт по этнографии. "
                                    "Отвечай строго JSON-массивом, без лишнего текста."
                                ),
                            },
                            {"role": "user", "content": prompt},
                        ],
                        "max_tokens": 8192,
                        "temperature": 0.0,
                        "reasoning": {"effort": "none"},
                    },
                    timeout=90,
                )

                # Debug: print status and token usage
                print(f"    HTTP {resp.status_code}, попытка {attempt+1}")

                if resp.status_code == 429:
                    if attempt == MAX_RETRIES - 1:
                        resp.raise_for_status()
                    delay = BASE_DELAY * (2 ** attempt)
                    print(
                        f"  [retry] LLM rate-limited (429), "
                        f"попытка {attempt+1}/{MAX_RETRIES}, ждём {delay:.0f}с..."
                    )
                    time.sleep(delay)
                    continue

                if resp.status_code >= 500:
                    if attempt == MAX_RETRIES - 1:
                        resp.raise_for_status()
                    delay = BASE_DELAY * (2 ** attempt)
                    print(
                        f"  [retry] LLM server error ({resp.status_code}): "
                        f"{resp.text[:200]}, "
                        f"попытка {attempt+1}/{MAX_RETRIES}, ждём {delay:.0f}с..."
                    )
                    time.sleep(delay)
                    continue

                resp.raise_for_status()
                data = resp.json()

                msg = data["choices"][0]["message"]
                raw = (msg.get("content") or "").strip()

                # Fallback: reasoning model may put answer in reasoning_content
                if not raw:
                    reasoning = (msg.get("reasoning_content") or "").strip()
                    if reasoning:
                        print(f"    (content пуст, использую reasoning_content "
                              f"({len(reasoning)} символов))")
                        raw = reasoning
                    else:
                        print(f"    ПРЕДУПРЕЖДЕНИЕ: и content, и reasoning_content "
                              f"пусты. Полный ответ: {json.dumps(data, ensure_ascii=False)[:500]}")
                        raise ValueError("Пустой ответ от модели (content и reasoning_content)")

                # Убираем возможные маркдаун-обёртки
                if raw.startswith("```"):
                    raw = re.sub(r"^```(?:json)?\s*", "", raw)
                    raw = re.sub(r"\s*```$", "", raw)

                result = json.loads(raw)
                if isinstance(result, list):
                    return result
                raise ValueError(f"Ожидался JSON-массив, получен {type(result)}: {raw[:200]}")

            except requests.exceptions.Timeout as e:
                if attempt == MAX_RETRIES - 1:
                    raise
                delay = BASE_DELAY * (2 ** attempt)
                print(
                    f"  [retry] LLM timeout, попытка {attempt+1}/{MAX_RETRIES}, "
                    f"ждём {delay:.0f}с..."
                )
                time.sleep(delay)
            except requests.exceptions.ConnectionError as e:
                if attempt == MAX_RETRIES - 1:
                    raise
                delay = BASE_DELAY * (2 ** attempt)
                print(
                    f"  [retry] LLM connection error, попытка {attempt+1}/{MAX_RETRIES}, "
                    f"ждём {delay:.0f}с..."
                )
                time.sleep(delay)
            except (json.JSONDecodeError, ValueError,
                    requests.exceptions.HTTPError) as e:
                if attempt == MAX_RETRIES - 1:
                    raise
                delay = BASE_DELAY * (2 ** attempt)
                print(
                    f"  [retry] LLM ошибка: {e}, попытка {attempt+1}/{MAX_RETRIES}, "
                    f"ждём {delay:.0f}с..."
                )
                time.sleep(delay)

        raise RuntimeError("unreachable")

    @staticmethod
    def _content_hash(rec: dict) -> str:
        """Хеш содержимого для дедупликации одинаковых записей."""
        key = "|".join([
            rec.get("title", "").strip().lower(),
            rec.get("functionality", "").strip(),
            rec.get("features", "").strip(),
            "|".join(sorted(m.strip().lower() for m in rec.get("materials", []))),
        ])
        return key

    # ------------------------------------------------------------------
    # Основной цикл классификации
    # ------------------------------------------------------------------
    def run(self):
        self.load_checkpoint()

        # 1) Скачать данные
        all_records = self.fetch_all()

        # Оставить только необработанные
        todo = [
            r for r in all_records
            if r.get("id") is not None and r["id"] not in self.processed_ids
        ]

        if not todo:
            print("Все записи уже обработаны (согласно чекпоинту).")
            self._write_final_output()
            return

        print(f"Осталось обработать: {len(todo)} записей (до дедупликации)")

        # 2) Rule-based проход
        rule_data: Dict[int, Tuple[Optional[str], Dict[str, int], List[str]]] = {}
        for rec in todo:
            rid = rec["id"]
            rule_data[rid] = rule_classify(rec)

        # 3) Дедупликация: группируем по содержимому, отправляем в LLM только по 1 представителю
        groups: Dict[str, List[dict]] = {}
        for rec in todo:
            h = self._content_hash(rec)
            groups.setdefault(h, []).append(rec)

        unique_count = len(groups)
        duplicate_savings = len(todo) - unique_count
        print(f"Уникальных записей для LLM: {unique_count} "
              f"(сэкономлено {duplicate_savings} дубликатов)")

        # Обратный индекс: id представителя -> хэш его группы, чтобы после
        # классификации представителя разослать тот же результат остальным
        # членам группы (иначе они навсегда выпадают из результата — баг,
        # найденный при ревью первого прогона).
        hash_by_rep_id: Dict[int, str] = {
            grp[0]["id"]: h for h, grp in groups.items()
        }

        # 4) LLM проход — батчами по уникальным представителям
        representatives = [grp[0] for grp in groups.values()]
        total = len(representatives)
        batch_start = 0
        batch_num = 0

        while batch_start < total:
            batch_end = min(batch_start + self.batch_size, total)
            batch = []
            for i in range(batch_start, batch_end):
                rec = representatives[i]
                rid = rec["id"]
                rl, _, rev = rule_data[rid]
                batch.append((rec, rl, rev))

            batch_num += 1
            pct = int(batch_start * 100 / total)
            print(f"\nLLM батч {batch_num} (записи {batch_start+1}-{batch_end}/{total}, {pct}%) ...")

            prompt = make_llm_batch_prompt(batch)
            try:
                llm_results = self._call_llm(prompt)
            except Exception as e:
                print(f"  Ошибка LLM после всех попыток: {e}")
                print(f"  Сохраняем записи этого батча с rule-based метками как final.")
                # fallback: использовать rule_label напрямую без LLM
                llm_results = []
                for rec, rl, rev in batch:
                    llm_results.append({
                        "id": rec["id"],
                        "label": rl or "unclear",
                        "reasoning": "LLM недоступен, использована rule-based метка.",
                        "agrees_with_rule": True,
                    })

            # 4) Собрать final_label и сохранить
            by_llm_id = {item["id"]: item for item in llm_results if "id" in item}

            for rec, rl, rev in batch:
                rid = rec["id"]
                _, scores, _ = rule_data[rid]
                llm_item = by_llm_id.get(rid)

                llm_label = llm_item.get("label") if llm_item else None
                llm_reasoning = llm_item.get("reasoning", "") if llm_item else ""

                # Итоговое решение
                if rl and llm_label and rl == llm_label:
                    final = rl
                    confidence = "high"
                    needs_review = False
                elif llm_label and llm_label != "unclear":
                    final = llm_label
                    confidence = "low"
                    needs_review = True
                else:
                    final = rl  # fallback на rule_label, если llm дал unclear
                    confidence = "low"
                    needs_review = True
                    if not final:
                        final = None

                def _build_result(source_rec: dict) -> dict:
                    return {
                        "id": source_rec["id"],
                        "title": source_rec.get("title", ""),
                        "catalog_id": source_rec.get("cat_id", ""),
                        "functionality": source_rec.get("functionality", ""),
                        "materials": source_rec.get("materials", []),
                        "creation_time": source_rec.get("creation_time", []),
                        "rule_label": rl,
                        "rule_scores": scores,
                        "rule_evidence": rev,
                        "llm_label": llm_label,
                        "llm_reasoning": llm_reasoning,
                        "final_label": final,
                        "confidence": confidence,
                        "needs_review": needs_review,
                    }

                self.save_checkpoint(_build_result(rec))

                # Разослать тот же результат остальным членам группы —
                # у них идентичный текст (title/functionality/features/
                # materials), поэтому классификация представителя для них
                # тоже верна. Без этого шага дубликаты навсегда выпадали
                # из итогового файла (см. ревью первого прогона).
                h = hash_by_rep_id.get(rid)
                if h:
                    for sibling in groups[h][1:]:
                        self.save_checkpoint(_build_result(sibling))

            batch_start = batch_end

        # 5) Финальный вывод
        self._write_final_output()

    # ------------------------------------------------------------------
    # Экспорт
    # ------------------------------------------------------------------
    def _write_final_output(self):
        """Записать итоговый JSON (массив) из накопленных результатов."""
        results_list = sorted(self.results.values(), key=lambda x: x.get("id", 0))
        with open(self.output_path, "w", encoding="utf-8") as f:
            json.dump(results_list, f, ensure_ascii=False, indent=2)
        print(f"\nИтоговый файл сохранён: {self.output_path}")
        self._print_summary(results_list)

    def _print_summary(self, results: List[dict]):
        """Сводка по категориям."""
        print("\n" + "=" * 60)
        print("СВОДКА КЛАССИФИКАЦИИ")
        print("=" * 60)

        counts: Dict[str, int] = {cat: 0 for cat in CATEGORIES}
        null_count = 0
        review_count = 0
        high_count = 0
        low_count = 0

        for r in results:
            fl = r.get("final_label")
            if fl and fl in counts:
                counts[fl] += 1
            elif fl is None:
                null_count += 1
            if r.get("needs_review"):
                review_count += 1
            if r.get("confidence") == "high":
                high_count += 1
            else:
                low_count += 1

        print(f"{'Категория':<40} {'Кол-во':>6}")
        print("-" * 48)
        for cat in CATEGORIES:
            print(f"{cat:<40} {counts[cat]:>6}")
        print("-" * 48)
        print(f"{'Не определено (null)':<40} {null_count:>6}")
        print(f"{'Требуют проверки (needs_review)':<40} {review_count:>6}")
        print()
        print(f"High confidence: {high_count}")
        print(f"Low confidence:  {low_count}")
        print(f"Всего записей:   {len(results)}")
        print("=" * 60)


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------
def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Автоклассификация коллекции «Этнография» по таксономии finding_type"
    )
    parser.add_argument(
        "--base-url",
        default=DEFAULT_BASE_URL,
        help=f"Базовый URL WP REST API (по умолчанию: {DEFAULT_BASE_URL})",
    )
    parser.add_argument(
        "--per-page",
        type=int,
        default=DEFAULT_PER_PAGE,
        help=f"Записей на странице при загрузке (по умолчанию: {DEFAULT_PER_PAGE})",
    )
    parser.add_argument(
        "--output",
        default=DEFAULT_OUTPUT,
        help=f"Путь к выходному JSON-файлу (по умолчанию: {DEFAULT_OUTPUT})",
    )
    parser.add_argument(
        "--model",
        default=DEFAULT_MODEL,
        help=f"ID модели DeepSeek (по умолчанию: {DEFAULT_MODEL})",
    )
    parser.add_argument(
        "--batch-size",
        type=int,
        default=DEFAULT_BATCH_SIZE,
        help=f"Размер батча для LLM (по умолчанию: {DEFAULT_BATCH_SIZE})",
    )
    parser.add_argument(
        "--limit",
        type=int,
        default=None,
        help="Ограничить количество записей (для тестового прогона)",
    )
    parser.add_argument(
        "--api-key",
        default=None,
        help="API-ключ DeepSeek (если не задана переменная DEEPSEEK_API_KEY)",
    )
    return parser.parse_args()


def main():
    args = parse_args()

    api_key = args.api_key or os.environ.get("DEEPSEEK_API_KEY", "")
    if not api_key:
        print(
            "ПРЕДУПРЕЖДЕНИЕ: DEEPSEEK_API_KEY не задан. "
            "LLM-классификация будет пропущена, все результаты — только rule-based.",
            file=sys.stderr,
        )

    runner = ClassificationRunner(
        base_url=args.base_url,
        per_page=args.per_page,
        output_path=args.output,
        model=args.model,
        batch_size=args.batch_size,
        limit=args.limit,
        api_key=api_key or None,
    )

    try:
        runner.run()
    except KeyboardInterrupt:
        print("\nПрервано пользователем. Прогресс сохранён в чекпоинт, "
              "при следующем запуске продолжится с того же места.",
              file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"\nОшибка: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()