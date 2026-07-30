Вот полное описание проекта одним сообщением, готовое для использования в Claude:

***

**Проект: «Историко-культурное наследие Кировской области»**

Просветительский веб-проект, посвящённый 90-летию образования Кировской области (5 декабря 1936 — 5 декабря 2026). Аудитория: жители области, туристы, краеведы, студенты. Концепция дизайна: «Дымковская игрушка встречает вятский северный модерн». [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/144130461/5e315055-f7ce-4ee3-96b4-5eb692da41f2/Prompt_dlya_Claude_lending_geobazy.md?AWSAccessKeyId=ASIA2F3EMEYE7KAL66MJ&Signature=MHck%2B5DlFd7S5tHZsL5lF%2FxQSUk%3D&x-amz-security-token=IQoJb3JpZ2luX2VjENX%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaCXVzLWVhc3QtMSJGMEQCIAuBp5UZMyF70SUvQ01vbnWgBEc9vsdrwmXP1ee1Cr4BAiAUdPN3GPUgZS8cp3m34S0cAX8FhseRYhEE7lqoiXEnuCr8BAid%2F%2F%2F%2F%2F%2F%2F%2F%2F%2F8BEAEaDDY5OTc1MzMwOTcwNSIM6ITZDmtS91YcaTNdKtAElbtbJRT3YI6m0Jb%2FU1q1aAlMo7iDv17K9AJYNJdr6TZdr%2BpiLC9Tfft1QCATiWP%2FWUmUkWElK%2Bo9CLGejdtxP7bkxoXCqk3RdRjrDEzmzX6vL%2BgKoBklECp3TcE3kSTJrrle2cWXb3WhHQ8aLbvUZUyg3JExzWBT4qxo5wvOvXLpAllbnI4z8U2LbxV%2FzdB9XkutFUvfbddjb0D74%2FaIZhkE0gKTNGGbFomBNrnZwCyNR%2BGjYZR3tPcki3Ru3PWpoIXr8ktkJfZGxs83DR%2Fq%2BDn9eFd1xn4p0HUYH4nElO1izR9KvrhAzyIwCH4eTEtbeOKo3jlbsgJGukvp7TP5jOyiPKtgc%2FLsp2oN4GnM8Q2xZUjBWgbp2tfMUqstqxJ%2F%2BmeAsVqK2walbSh4M7DZ%2F8jHC%2Bg7pdsMQYWwWIXKTw4ys8a88ewdTLsJke8aicszwmP1pYiz9K2tNnGmRUTFOuPjINWoFFIImX47ARcjLWiqnep7oicaxrXVTJjrDa1dq1Moc4cffPFfsgD7y4z%2BCoxlbnit3RYyI9jDLpyv0aYYwgkA%2FPfWAtmiMlcaMrBEPNznzy1mBDIndcO3E9MNDIGlS1RPG8zeMK90mSDnN3JmsvADIUsIvGdU39LrU9zh3UW1m54y1%2FeQseSG5lt134BtCkLeUjQHNwF9HRgMu7LjrmiFO87RXBgjxiXeLRBIjk%2BQqUiHnCrZKD9ZOqYQDzGH1kIm3UNJv1qSMYp2s8eMwXiQm6iCXdSFZBea%2FoL2yHzf5jeR03fLbgShb%2FKcGzD9467TBjqZAQryRIZnFLLyoNbK5GZbiTqt6U%2BsujVYGwoLlXZAFTHdpBjp%2F2uML9QZ4GbHD7vPjnCEn3FtrLsL5OVA9sK4Lbvcp64mzKgGgI40F%2BT%2FP5N1fd4mafjywhFiqGMn0pgah94GXW5P%2BvIx2ohIBONgXn7Vcdzijg%2B3Bf27U8QVr%2FbJExfXX7etbkfR42GxLBRNA6ja27AUeRqikQ%3D%3D&Expires=1785446352)

**Общая архитектура**

Проект реализован как лендинг-навигатор, объединяющий единым дизайном семь самостоятельных тематических модулей. Backend реализован на WordPress API: каждый модуль подгружает контент из своей базы данных через REST-эндпоинт (custom post type), что разделяет статичную презентационную оболочку (главная страница, навигация, брендинг) и динамический контент, пополняемый через админку WordPress без изменения фронтенда.

Структура главной страницы (сверху вниз): sticky header с навигацией по всем модулям → hero-блок с параллаксом и CTA-кнопкой «Исследовать наследие» → историческая справка (текст + вертикальный таймлайн XII в.—2026 г.) → блок народных промыслов (3 карточки: дымковская игрушка, капокорень, кружевоплетение) → переходный блок «Архитектура» с параллаксом → сетка из 7 карточек-модулей (id="modules") → блок с цитатой → footer с повторным перечнем разделов.

**Состав и структура семи модулей**

1. **Лица Победы** — каталог карточек (фото + описание), клик по карточке ведёт на страницу персоны с биографией, наградными листами и боевым путём. Хронологический охват — от XV века до новейшего времени.

2. **Исторические города Кировской области** — на главной странице модуля интерактивная карта (метки — 7 городов: Киров, Котельнич, Орлов, Слободской, Яранск, Малмыж, Уржум). Клик по метке ведёт на страницу города, где контент представлен как лента времени с карточками-записями, упорядоченными по годам, с фото и заголовком. Клик по карточке ленты ведёт на отдельную страницу события.

3. **Выдающиеся памятники археологии области** — интерактивная карта с метками (в попапе — фото и описание памятника, всего около 150 объектов). Клик по метке/попапу ведёт на страницу памятника с описанием ландшафта, историей исследований и данными о сохранности.

4. **Этнографическое наследие Кировской области** — каталог карточек (фото, название, краткое описание предмета/артефакта). Клик ведёт на страницу предмета с указанием места и времени бытования, этноса (русские, татары, удмурты, марийцы, коми).

5. **Архитектура города Кирова** — карта объектов (фото + название), клик по метке ведёт на страницу объекта с историческим и современным местоположением, стилем, декором, архивными изображениями (включая утраченные памятники и скульптуру).

6. **Памятники природы Кировской области** — свыше 100 охраняемых объектов с геопривязкой, описанием происхождения и режима посещения.

7. **Археология города Кирова** — материалы раскопок исторического центра (керамика, монеты, укрепления детинца).

**Карта: Яндекс.Карты версии 3**

Три модуля (исторические города, памятники археологии, архитектура Кирова) используют интерактивную карту на Яндекс.Картах API v3 как основной навигационный интерфейс:
- Метки кластеризуются при большом количестве объектов (актуально для ~150 памятников археологии).
- Попап метки содержит превью-карточку (фото + заголовок/краткое описание), клик по попапу или метке ведёт на страницу объекта.
- Карта синхронизирована с фильтрами: применение фильтра скрывает нерелевантные метки без перезагрузки страницы.
- Для модуля «Исторические города» карта — навигационный хаб верхнего уровня (7 меток), детальный контент раскрывается только на вложенной странице города в виде ленты времени.

**Единая система фильтров**

Один и тот же визуальный компонент фильтра используется во всех модулях — как над каталогом карточек, так и над картой. Набор параметров адаптируется к контенту модуля (период/война для «Лиц Победы», этнос для этнографии, эпоха для археологии, стиль для архитектуры), но визуальное поведение фильтра (расположение, анимация, состояния) остаётся идентичным везде. Результат фильтрации: в каталогах скрываются нерелевантные карточки, на картах — скрываются/подсвечиваются нерелевантные метки.

**Единая навигация между страницами**

- Хлебные крошки на детальных страницах (Главная → Модуль → Объект) для возврата на любой уровень.
- Кнопка «Назад к каталогу/карте» на всех детальных страницах.
- Общий header с якорной навигацией по всем 7 модулям доступен на любой странице сайта, включая внутренние страницы и карточки объектов.

**Дизайн-система**

Цвета (CSS-переменные): --color-bg #FDFBF7 (топлёное молоко), --color-teal #3C7A8C (вятская бирюза), --color-ochre #C27E3A (ржаная охра), --color-oak #E8DFC8 (белёный дуб), --color-ink #2A2118 (чернильный), --color-birch #F5F2EC (берёза). [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/144130461/5e315055-f7ce-4ee3-96b4-5eb692da41f2/Prompt_dlya_Claude_lending_geobazy.md?AWSAccessKeyId=ASIA2F3EMEYE7KAL66MJ&Signature=MHck%2B5DlFd7S5tHZsL5lF%2FxQSUk%3D&x-amz-security-token=IQoJb3JpZ2luX2VjENX%2F%2F%2F%2F%2F%2F%2F%2F%2F%2FwEaCXVzLWVhc3QtMSJGMEQCIAuBp5UZMyF70SUvQ01vbnWgBEc9vsdrwmXP1ee1Cr4BAiAUdPN3GPUgZS8cp3m34S0cAX8FhseRYhEE7lqoiXEnuCr8BAid%2F%2F%2F%2F%2F%2F%2F%2F%2F%2F8BEAEaDDY5OTc1MzMwOTcwNSIM6ITZDmtS91YcaTNdKtAElbtbJRT3YI6m0Jb%2FU1q1aAlMo7iDv17K9AJYNJdr6TZdr%2BpiLC9Tfft1QCATiWP%2FWUmUkWElK%2Bo9CLGejdtxP7bkxoXCqk3RdRjrDEzmzX6vL%2BgKoBklECp3TcE3kSTJrrle2cWXb3WhHQ8aLbvUZUyg3JExzWBT4qxo5wvOvXLpAllbnI4z8U2LbxV%2FzdB9XkutFUvfbddjb0D74%2FaIZhkE0gKTNGGbFomBNrnZwCyNR%2BGjYZR3tPcki3Ru3PWpoIXr8ktkJfZGxs83DR%2Fq%2BDn9eFd1xn4p0HUYH4nElO1izR9KvrhAzyIwCH4eTEtbeOKo3jlbsgJGukvp7TP5jOyiPKtgc%2FLsp2oN4GnM8Q2xZUjBWgbp2tfMUqstqxJ%2F%2BmeAsVqK2walbSh4M7DZ%2F8jHC%2Bg7pdsMQYWwWIXKTw4ys8a88ewdTLsJke8aicszwmP1pYiz9K2tNnGmRUTFOuPjINWoFFIImX47ARcjLWiqnep7oicaxrXVTJjrDa1dq1Moc4cffPFfsgD7y4z%2BCoxlbnit3RYyI9jDLpyv0aYYwgkA%2FPfWAtmiMlcaMrBEPNznzy1mBDIndcO3E9MNDIGlS1RPG8zeMK90mSDnN3JmsvADIUsIvGdU39LrU9zh3UW1m54y1%2FeQseSG5lt134BtCkLeUjQHNwF9HRgMu7LjrmiFO87RXBgjxiXeLRBIjk%2BQqUiHnCrZKD9ZOqYQDzGH1kIm3UNJv1qSMYp2s8eMwXiQm6iCXdSFZBea%2FoL2yHzf5jeR03fLbgShb%2FKcGzD9467TBjqZAQryRIZnFLLyoNbK5GZbiTqt6U%2BsujVYGwoLlXZAFTHdpBjp%2F2uML9QZ4GbHD7vPjnCEn3FtrLsL5OVA9sK4Lbvcp64mzKgGgI40F%2BT%2FP5N1fd4mafjywhFiqGMn0pgah94GXW5P%2BvIx2ohIBONgXn7Vcdzijg%2B3Bf27U8QVr%2FbJExfXX7etbkfR42GxLBRNA6ja27AUeRqikQ%3D%3D&Expires=1785446352)

Шрифты: заголовки — Playfair Display (H1 52px/32px, H2 36px/26px, H3 22px/18px, desktop/mobile), текст — Roboto Slab (body 16px, line-height 1.75).

Отступы: шаг сетки 8px (8/16/24/40/64/96px), border-radius блоков 4px. Все карточки — border 1.5px dashed #C27E3A («берестяная строчка»). Кнопки — форма «фигурной скобы» через clip-path, с состояниями default/hover/active/focus. Фоновый узор — дымковский орнамент через SVG с медленным drift-анимированием.

**Технические требования**

Mobile First (320px—2560px), breakpoints 768/1024/1280px, кроссбраузерность Chrome/Firefox 100+/Safari 15+. Scroll-анимации через IntersectionObserver (fadeInUp, fadeInLeft), параллакс-эффекты в hero и архитектурном блоке, адаптивное мобильное меню (сайдбар справа), sticky header. Все анимации отключаются при prefers-reduced-motion: reduce, обязательны фокус-стили и alt-тексты для доступности, изображения — loading="lazy" с fallback-фоном при недоступности.