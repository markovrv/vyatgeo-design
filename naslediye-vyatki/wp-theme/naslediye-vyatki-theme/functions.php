<?php
/**
 * Тема-«оболочка» для headless-режима WordPress.
 *
 * Идея: WP остаётся источником данных (REST API, /wp-json/) и админкой
 * (/wp-admin/), но ничего своего на фронте больше не рендерит — вместо
 * стандартного вывода страниц/записей/архивов/поиска/404 на любой фронтовый
 * URL отдаётся собранный прод-билд Vue-приложения naslediye-vyatki (dist/,
 * см. `npm run build:theme` в корне проекта). Vue Router работает в
 * history-режиме без base (см. src/router/index.js), поэтому сервер должен
 * на любой такой URL отдавать один и тот же index.html — дальше маршрутизацию
 * берёт на себя сам SPA.
 *
 * wp-admin, wp-login.php, wp-cron.php и REST-запросы (/wp-json/...) не
 * проходят через хук template_redirect (у них свой bootstrap, WP завершает
 * их обработку раньше) — этим кодом они не затрагиваются.
 */

define('NV_SPA_DIST_DIR', get_template_directory() . '/dist');
define('NV_SPA_INDEX_FILE', NV_SPA_DIST_DIR . '/index.html');

require_once get_template_directory() . '/admin/import.php';
require_once get_template_directory() . '/admin/i18n-strings.php';
require_once get_template_directory() . '/menu-api.php';

add_action('after_setup_theme', 'nv_spa_theme_setup');
add_action('after_switch_theme', 'nv_spa_ensure_default_nav_menus');
add_action('template_redirect', 'nv_spa_bootstrap', 0);
add_action('customize_register', 'nv_spa_customize_register');

/**
 * WP-ядро по умолчанию отклоняет анонимный POST /wp/v2/comments (401
 * rest_comment_login_required) в WP_REST_Comments_Controller::create_item_permissions_check(),
 * НЕЗАВИСИМО от настройки «Разрешить комментарии» в Настройки → Обсуждение —
 * это отдельная проверка (фильтр rest_allow_anonymous_comments, по умолчанию
 * false), которая распространяется только на REST API. Без него форма
 * комментариев в SPA (гостевая, без логина) не работает вообще ни для одной
 * сущности — проверено вживую в Docker (401 на любую попытку отправки).
 */
add_filter('rest_allow_anonymous_comments', '__return_true');

function nv_spa_theme_setup() {
    register_nav_menus([
        'spa_primary' => 'Главное меню SPA',
        'spa_footer'  => 'Меню в футере SPA',
    ]);

    // Без add_theme_support('post-thumbnails') редактор вообще не показывает
    // панель «Изображение записи» ни для одного типа записи — у attraction/
    // finding/history фото до сих пор попадали только через импорт-скрипты
    // (set_post_thumbnail), минуя админку. У стандартного типа 'page' плюс
    // к этому 'thumbnail' по умолчанию не входит в его supports вообще (в
    // отличие от 'post') — добавляем отдельно, иначе даже при включённой
    // теме-поддержке панель на странице не появится. Нужно для hero-фото
    // WP-страниц в PageView.vue (see usePage.js — featured image).
    add_theme_support('post-thumbnails');
    add_post_type_support('page', 'thumbnail');

    nv_spa_ensure_default_nav_menus();
    nv_spa_ensure_about_page();
}

/**
 * Дублирует захардкоженную навигацию SPA (HARDCODED_NAV в
 * src/layouts/SiteHeader.vue, HARDCODED_FOOTER_NAV в src/layouts/SiteFooter.vue)
 * в реальные меню WordPress на локациях spa_primary/spa_footer — чтобы
 * редактор сразу видел готовую структуру в Внешний вид → Меню и мог её
 * менять, а не собирал заново вручную. Срабатывает при активации темы
 * (after_switch_theme) и подстраховочно на каждой загрузке (after_setup_theme)
 * — но реально создаёт меню только один раз для каждой локации: если ей уже
 * назначено какое-либо меню (даже пустое или изменённое редактором),
 * соответствующая локация пропускается и не трогается.
 */
function nv_spa_ensure_default_nav_menus() {
    nv_spa_ensure_default_menu('spa_primary', 'Главное меню SPA', [
        ['title' => 'Главная',     'url' => '/'],
        ['title' => 'Лица Победы', 'url' => '/victory'],
        ['title' => 'Этнография',  'url' => '/ethnography'],
        ['title' => 'Города',      'url' => '/cities'],
        ['title' => 'Природа',     'url' => '/nature'],
        ['title' => 'Археология',  'url' => '/archeology'],
        ['title' => 'Архитектура', 'url' => '/architecture'],
        ['title' => 'О проекте',   'url' => '/about'],
    ]);

    nv_spa_ensure_default_menu('spa_footer', 'Меню в футере SPA', [
        ['title' => 'Главная',                    'url' => '/'],
        ['title' => 'Лица Победы',                'url' => '/victory'],
        ['title' => 'Этнографическое наследие',   'url' => '/ethnography'],
        ['title' => 'Исторические города',        'url' => '/cities'],
        ['title' => 'Памятники природы',          'url' => '/nature'],
        ['title' => 'Памятники археологии',       'url' => '/archeology'],
        ['title' => 'Архитектура Кирова',         'url' => '/architecture'],
        ['title' => 'О проекте',                  'url' => '/about'],
    ]);
}

function nv_spa_ensure_default_menu($location, $menu_name, $default_items) {
    $locations = get_nav_menu_locations();
    if (!empty($locations[$location])) {
        return;
    }

    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? $menu->term_id : wp_create_nav_menu($menu_name);
    if (is_wp_error($menu_id) || !$menu_id) {
        return;
    }

    foreach ($default_items as $position => $item) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'    => $item['title'],
            'menu-item-url'      => $item['url'],
            'menu-item-type'     => 'custom',
            'menu-item-status'   => 'publish',
            'menu-item-position' => $position + 1,
        ]);
    }

    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}

/**
 * Страница «О проекте» раньше жила захардкоженным Vue-компонентом
 * (AboutView.vue) — перенесена в обычную WP-страницу (slug "about"),
 * рендерится общим PageView.vue (см. src/router/index.js), как и любая
 * другая страница. Верстка (hero, карточки статистики, сетка модулей,
 * CTA) перенесена в HTML-разметку контента, стилизованную классами,
 * которые PageView.vue понимает для любой WP-страницы (не только этой) —
 * см. .content :deep(.stat-grid/.module-grid/.cta-block) в PageView.vue.
 * Создаётся один раз при активации темы, если страницы с таким slug ещё
 * нет — дальше содержимое полностью редактируется в wp-admin, повторный
 * запуск ничего не перезаписывает.
 */
function nv_spa_ensure_about_page() {
    $existing = get_page_by_path('about', OBJECT, 'page');
    if ($existing) {
        return;
    }

    $content = <<<'HTML'
<p><strong>«Наследие Вятки»</strong> — просветительский проект, приуроченный к 90-летию образования Кировской области (5 декабря 1936 — 5 декабря 2026). Его цель — собрать, сохранить и сделать доступными для широкой аудитории уникальные материалы по истории, культуре и природе Вятской земли.</p>
<p>Вятский край — один из самобытнейших регионов России, где на протяжении столетий переплетались культурные традиции русских, татар, удмуртов, марийцев и коми. Здесь формировался уникальный архитектурный облик городов, возникли прославленные народные промыслы — дымковская игрушка, вятское кружево, изделия из капокорня.</p>
<p>Проект объединяет шесть тематических модулей, каждый из которых посвящён одному из аспектов наследия региона: от средневековой археологии до современной архитектуры, от воинской славы до этнографического многообразия.</p>

<h2>Проект в цифрах</h2>
<div class="stat-grid">
<div class="stat-card"><span class="stat-n">6</span><span class="stat-label">тематических модулей</span></div>
<div class="stat-card"><span class="stat-n">10+</span><span class="stat-label">страниц контента</span></div>
<div class="stat-card"><span class="stat-n">более 70</span><span class="stat-label">описанных объектов</span></div>
<div class="stat-card"><span class="stat-n">~150</span><span class="stat-label">памятников археологии</span></div>
<div class="stat-card"><span class="stat-n">более 100</span><span class="stat-label">памятников природы</span></div>
<div class="stat-card"><span class="stat-n">5</span><span class="stat-label">народов региона</span></div>
</div>

<h2>Модули проекта</h2>
<div class="module-grid"><a class="module-card" href="/victory"><span class="module-title">Лица Победы</span><span class="module-desc">Живая летопись ратной славы Вятской земли с XIV века до наших дней.</span></a><a class="module-card" href="/cities"><span class="module-title">Исторические города</span><span class="module-desc">Ленты времени семи городов: Кирова, Котельнича, Орлова, Слободского, Яранска, Малмыжа и Уржума.</span></a><a class="module-card" href="/archeology"><span class="module-title">Памятники археологии</span><span class="module-desc">Около 150 памятников от эпохи мезолита до позднего Средневековья.</span></a><a class="module-card" href="/ethnography"><span class="module-title">Этнографическое наследие</span><span class="module-desc">Коллекция предметов быта, костюмов и обрядов народов Вятского края.</span></a><a class="module-card" href="/architecture"><span class="module-title">Архитектура Кирова</span><span class="module-desc">Цифровой архив зданий и памятников областного центра.</span></a><a class="module-card" href="/nature"><span class="module-title">Памятники природы</span><span class="module-desc">Более 100 уникальных природных объектов региона.</span></a></div>

<h2>Научная основа</h2>
<p>Материалы проекта базируются на многолетних исследованиях:</p>
<ul>
<li>Археологические экспедиции Кировского государственного педагогического института им. В.И. Ленина</li>
<li>Этнографические экспедиции Вятского государственного гуманитарного университета</li>
<li>Раскопки Кировского областного краеведческого музея</li>
<li>Работы архитекторов и краеведов Кировской организации Союза архитекторов России</li>
<li>Исследования природных заказников и памятников природы регионального значения</li>
</ul>

<h2>Партнёры и поддержка</h2>
<p>Проект реализован при поддержке:</p>
<ul>
<li>Правительства Кировской области</li>
<li>Кировского областного краеведческого музея</li>
<li>Вятского государственного университета</li>
<li>Кировской организации Союза архитекторов России</li>
</ul>

<h3>Как помочь проекту</h3>
<p>Если у вас есть фотографии, документы, письма или предметы быта, связанные с историей Вятского края, — пришлите их нам для оцифровки и публикации.</p>

<div class="cta-block">
<p>Проект «Наследие Вятки» — это живой архив, который будет пополняться новыми материалами</p>
<a class="cta-btn" href="mailto:info@vyatka-heritage.ru">Написать нам</a>
</div>
HTML;

    wp_insert_post([
        'post_title'   => 'О проекте',
        'post_name'    => 'about',
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'comment_status' => 'closed',
    ]);
}

function nv_spa_bootstrap() {
    // Служебные виртуальные эндпоинты самого WP не трогаем.
    if (is_robots() || is_favicon() || is_feed() || is_trackback() || is_customize_preview()) {
        return;
    }

    $request_path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Сравнение с текущим путём защищает от редиректа самого в себя: у
    // некоторых сущностей (пока только страница "about") реальный
    // WP-пермалинк совпадает с её собственным SPA-маршрутом — без этой
    // проверки nv_spa_resolve_wp_permalink_redirect() зациклил бы 302 на
    // тот же самый /about.
    $redirect_url = nv_spa_resolve_wp_permalink_redirect($request_path);
    if ($redirect_url && untrailingslashit(parse_url($redirect_url, PHP_URL_PATH)) !== untrailingslashit($request_path)) {
        wp_safe_redirect($redirect_url, 302);
        exit;
    }

    if (nv_spa_serve_static_asset($request_path)) {
        exit;
    }

    nv_spa_render_app();
    exit;
}

/**
 * Внутри content записей (ссылки между объектами, вставленные через
 * редактор), в guid/ссылках комментариев и т.п. встречаются обычные
 * WP-пермалинки — вида ?p=123, ?page_id=123, ?attachment_id=123,
 * ?attraction=slug, ?finding=slug, ?history=slug, ?city=slug (для CPT/
 * таксономий без своего rewrite-правила WP всегда падает на такой
 * query-var формат, независимо от текущего permalink_structure). SPA их не
 * генерирует и не умеет разбирать — переход по такой ссылке раньше просто
 * открывал SPA-шелл на "/", то есть вместо ожидаемой записи показывал
 * главную. К моменту template_redirect WP уже резолвил объект через
 * штатный WP_Query, поэтому здесь его можно взять глобальными is_*()/
 * get_queried_object() и 302-редиректнуть на соответствующий client-side
 * маршрут SPA. Если объект резолвится (is_singular()/is_tax()/is_attachment()),
 * но для его типа маршрута в SPA ещё нет (hero, oopt, geopoints — см.
 * CLAUDE.md, у них пока нет фронтенда) — возвращаем null и ведём себя как
 * раньше (просто открываем SPA-шелл), не пытаясь угадать несуществующий путь.
 */
function nv_spa_resolve_wp_permalink_redirect($request_path) {
    if (is_attachment()) {
        $attachment = get_queried_object();
        if ($attachment instanceof WP_Post && $attachment->post_parent) {
            $parent_path = nv_spa_post_to_spa_path(get_post($attachment->post_parent));
            if ($parent_path) {
                return home_url($parent_path);
            }
        }

        // Вложение-сирота (без родителя) у WP по умолчанию живёт на
        // одном сегменте URL, совпадающем с slug'ом файла (/%postname%/,
        // как у страниц) — если в медиатеку когда-либо загрузят файл
        // с именем, совпадающим с одним из корневых маршрутов SPA
        // (например, nature.jpg → вложение со slug "nature"), WP начнёт
        // резолвить /nature именно в это вложение раньше, чем в раздел
        // SPA. Для настоящих ссылок на вложение-сироту (из старого
        // контента) редирект на файл уместен, но не тогда, когда адрес
        // на самом деле — зарезервированный маршрут SPA, а совпадение
        // со slug'ом вложения случайно.
        if (!nv_spa_is_reserved_route_root($request_path)) {
            return wp_get_attachment_url(get_queried_object_id()) ?: null;
        }

        return null;
    }

    if (is_singular()) {
        $path = nv_spa_post_to_spa_path(get_queried_object());
        return $path ? home_url($path) : null;
    }

    if (is_tax('city')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            return home_url('/cities/' . $term->slug);
        }
    }

    return null;
}

function nv_spa_post_to_spa_path($post) {
    if (!$post instanceof WP_Post) {
        return null;
    }

    switch ($post->post_type) {
        case 'post':
            return '/news/' . $post->ID;
        case 'page':
            // "about" — не обычная WP-страница из общего /page/:slug, у неё
            // своя закреплённая точка входа /about (см. src/router/index.js);
            // без этого частного случая переход по прямой ссылке /about
            // 302-редиректился бы сам в себя же на /page/about.
            if ($post->post_name === 'about') {
                return '/about';
            }
            return '/page/' . $post->post_name;
        case 'attraction':
            return '/architecture/' . $post->ID;
        case 'finding':
            return '/ethnography/' . $post->ID;
        case 'history':
            $terms = get_the_terms($post->ID, 'city');
            $city_slug = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : 'kirov';
            return '/cities/' . $city_slug . '/event/' . $post->ID;
        default:
            // hero, oopt, geopoints и другие типы без собственной страницы
            // объекта в SPA — маршрута для них ещё нет.
            return null;
    }
}

/**
 * Корневые сегменты маршрутов SPA (первый сегмент пути у каждого
 * верхнеуровневого route из src/router/index.js) — используется только для
 * защиты от коллизии со slug'ом вложения-сироты, см. вызов в
 * nv_spa_resolve_wp_permalink_redirect(). Список поддерживается вручную и не
 * обязан включать вообще все маршруты SPA — только его собственные корневые
 * разделы (не /page/:slug и не пути объектов вида /architecture/:id, у них
 * своя проверка через post_type выше).
 */
function nv_spa_is_reserved_route_root($request_path) {
    static $reserved = ['victory', 'ethnography', 'cities', 'nature', 'archeology', 'architecture', 'about', 'news', 'page', 'archives'];
    $segment = explode('/', trim((string) $request_path, '/'))[0] ?? '';
    return in_array($segment, $reserved, true);
}

/**
 * Ассеты билда лежат по абсолютным от корня путям (/assets/…, /polygons/…) —
 * так их собирает Vite (base: '/') и так их ищет браузер, поскольку сам
 * index.html теперь может открываться по любому фронтовому URL, а не только
 * из корня. Отдаём файл напрямую из dist/, если запрошенный путь туда
 * попадает; иначе не вмешиваемся.
 */
function nv_spa_serve_static_asset($path) {
    $allowed_prefixes = ['/assets/', '/polygons/'];

    $matched = false;
    foreach ($allowed_prefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        return false;
    }

    $dist_root = realpath(NV_SPA_DIST_DIR);
    $file = $dist_root !== false ? realpath($dist_root . $path) : false;

    // Защита от directory traversal: итоговый путь обязан остаться внутри dist/.
    if ($file === false || $dist_root === false || !str_starts_with($file, $dist_root)) {
        return false;
    }
    if (!is_file($file)) {
        return false;
    }

    nv_spa_send_file($file);
    return true;
}

function nv_spa_send_file($file) {
    $mime_types = [
        'js'      => 'text/javascript; charset=UTF-8',
        'css'     => 'text/css; charset=UTF-8',
        'json'    => 'application/json; charset=UTF-8',
        'geojson' => 'application/geo+json; charset=UTF-8',
        'svg'     => 'image/svg+xml',
        'png'     => 'image/png',
        'jpg'     => 'image/jpeg',
        'jpeg'    => 'image/jpeg',
        'webp'    => 'image/webp',
        'mp4'     => 'video/mp4',
        'woff'    => 'font/woff',
        'woff2'   => 'font/woff2',
        'ico'     => 'image/x-icon',
    ];

    // WP уже мог выставить статус 404 в WP::send_headers() (до template_redirect,
    // на котором висит эта тема) — файл найден и отдаётся, поэтому статус
    // нужно явно переустановить, иначе браузер получит 200-по-содержимому,
    // но 404-по HTTP-статусу и откажется исполнять js/css (ERR_ABORTED).
    status_header(200);

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mime_types[$ext] ?? 'application/octet-stream'));

    // Файлы с content-хэшем Vite в имени можно кэшировать надолго и неизменяемо;
    // при следующей сборке у изменившегося файла будет уже другое имя.
    if (preg_match('/-[A-Za-z0-9_-]{8}\.(?:js|css)$/', basename($file))) {
        header('Cache-Control: public, max-age=31536000, immutable');
    } else {
        header('Cache-Control: public, max-age=3600');
    }

    header('Content-Length: ' . filesize($file));
    readfile($file);
}

function nv_spa_customize_register($wp_customize) {
    $wp_customize->add_section('nv_spa_config', [
        'title'    => __('Конфигурация приложения', 'naslediye-vyatki-theme'),
        'priority' => 120,
    ]);

    $wp_customize->add_setting('nv_api_base_url', [
        'default'           => 'https://testsite.vyatgeo.ru/wp-json/',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('nv_api_base_url', [
        'label'       => __('API Base URL', 'naslediye-vyatki-theme'),
        'description' => __('Базовый URL WordPress REST API (с /wp-json/ на конце).', 'naslediye-vyatki-theme'),
        'section'     => 'nv_spa_config',
        'type'        => 'url',
    ]);

    $wp_customize->add_setting('nv_yandex_maps_api_key', [
        'default'           => '47e70ac9-78de-4c21-9fba-ffd8e5855ee6',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('nv_yandex_maps_api_key', [
        'label'       => __('Yandex Maps API Key', 'naslediye-vyatki-theme'),
        'description' => __('Ключ API Яндекс.Карт для модуля «Архитектура Кирова».', 'naslediye-vyatki-theme'),
        'section'     => 'nv_spa_config',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('nv_attraction_nearby_radius_km', [
        'default'           => 1.5,
        'sanitize_callback' => function ($value) {
            return (float) $value;
        },
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('nv_attraction_nearby_radius_km', [
        'label'       => __('Радиус «Объекты рядом» (км)', 'naslediye-vyatki-theme'),
        'description' => __('Максимальное расстояние для блока «Объекты рядом» на странице объекта архитектуры.', 'naslediye-vyatki-theme'),
        'section'     => 'nv_spa_config',
        'type'        => 'number',
        'input_attrs' => [
            'min'  => 0.1,
            'max'  => 100,
            'step' => 0.1,
        ],
    ]);
}

function nv_spa_customizer_script() {
    return sprintf(
        "<script>\n"
        . "/* Конфигурация из Customizer — переопределяет значения из index.html */\n"
        . "window.__API_BASE_URL__ = '%s';\n"
        . "window.__YANDEX_MAPS_API_KEY__ = '%s';\n"
        . "window.__ATTRACTION_NEARBY_RADIUS_KM__ = %s;\n"
        . "</script>\n",
        esc_js(get_theme_mod('nv_api_base_url', 'https://testsite.vyatgeo.ru/wp-json/')),
        esc_js(get_theme_mod('nv_yandex_maps_api_key', '47e70ac9-78de-4c21-9fba-ffd8e5855ee6')),
        (float) get_theme_mod('nv_attraction_nearby_radius_km', 1.5)
    );
}

/**
 * Nonce для REST-запросов от имени залогиненного пользователя (форма
 * комментариев подставляет его имя/e-mail через /wp/v2/users/me и шлёт при
 * отправке комментария через X-WP-Nonce — см. src/composables/useComments.js).
 * Браузер и так шлёт куку сессии на любой fetch к тому же домену независимо
 * от того, ждём мы этого или нет; без валидного nonce WP либо не признаёт
 * пользователя залогиненным вовсе (GET /users/me отдаёт 401), либо отклоняет
 * запись (POST /wp/v2/comments — 403) — то есть без этого блока залогиненный
 * в WP посетитель не смог бы отправить комментарий с сайта вообще, даже
 * анонимно, потому что кука уже "представляется" WP, а nonce для неё нет.
 * Для гостя (не залогинен) window.__WP_REST_NONCE__ не выводится вовсе.
 */
function nv_spa_user_script() {
    if (!is_user_logged_in()) {
        return '';
    }
    return sprintf(
        "<script>\nwindow.__WP_REST_NONCE__ = '%s';\n</script>\n",
        esc_js(wp_create_nonce('wp_rest'))
    );
}

/**
 * Строки интерфейса из админки «Внешний вид → Строки интерфейса».
 * Редактор сохраняет переопределения в опцию nv_i18n_strings (ассоциативный
 * массив dot-path → значение). SPA читает window.__I18N_STRINGS__ и накладывает
 * их поверх дефолтных значений из своего бандла — см. src/i18n/index.js.
 *
 * Если опция пустая (ни одного переопределения), скрипт не выводится.
 */
function nv_spa_i18n_script() {
    $overrides = get_option('nv_i18n_strings', []);
    if (empty($overrides) || !is_array($overrides)) return '';
    $json = json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return "<script>\nwindow.__I18N_STRINGS__ = $json;\n</script>\n";
}

function nv_spa_render_app() {
    if (!is_file(NV_SPA_INDEX_FILE)) {
        status_header(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Сборка приложения не найдена.\nВыполните `npm run build:theme` в проекте naslediye-vyatki и активируйте тему заново.";
        return;
    }

    status_header(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, must-revalidate');

    $html = file_get_contents(NV_SPA_INDEX_FILE);
    $html = str_replace('</head>', nv_spa_customizer_script() . nv_spa_user_script() . nv_spa_i18n_script() . nv_spa_admin_bar_head_html() . '</head>', $html);

    if (is_admin_bar_showing()) {
        $html = nv_spa_add_body_class($html, 'admin-bar');
        $html = nv_spa_inject_after_body_open($html, nv_spa_admin_bar_body_html());
    }

    echo $html;
}

/**
 * Панель администратора (wp-admin toolbar) поверх статического SPA.
 *
 * dist/index.html отдаётся как обычный статический файл — без вызова
 * wp_head()/wp_footer(), на которые в стандартной теме вешаются enqueue
 * и печать ассетов панели, поэтому сама по себе она не появляется, хотя
 * WP ей полностью управляет (видимость, пункты меню, права) через свой
 * стандартный механизм. К моменту вызова этой функции панель уже
 * инициализирована: _wp_admin_bar_init() висит на том же хуке
 * template_redirect с приоритетом 0, но зарегистрирован раньше
 * nv_spa_bootstrap (это дефолтный хук самого ядра, подключается до того,
 * как загружается functions.php темы) — глобальный $wp_admin_bar и очередь
 * ассетов admin-bar/dashicons уже готовы, остаётся только вручную
 * допечатать их в статическую разметку.
 *
 * is_admin_bar_showing() — тот же стандартный чек, что использует само
 * ядро: залогиненный пользователь и его личная настройка «Показывать
 * панель инструментов» (профиль → показывать панель при просмотре сайта).
 * Отдельная проверка на роль/capability не нужна — на проекте это и так
 * единственный админ-аккаунт, а поведение остаётся штатным для WP.
 */
function nv_spa_admin_bar_head_html() {
    if (!is_admin_bar_showing()) {
        return '';
    }

    // В обычной теме эти две функции вызывает wp_enqueue_scripts() из
    // wp_head() — добавляют на хендл admin-bar инлайн-CSS (отступ под
    // панель сверху страницы + скрытие панели при печати).
    wp_enqueue_admin_bar_bump_styles();
    wp_enqueue_admin_bar_header_styles();

    ob_start();
    wp_styles()->do_items(['admin-bar']);
    return ob_get_clean();
}

function nv_spa_admin_bar_body_html() {
    if (!is_admin_bar_showing()) {
        return '';
    }

    ob_start();
    wp_admin_bar_render();
    // Скрипт — после разметки, а не до: admin-bar.js навешивает обработчики
    // на #wpadminbar через hoverIntent, элемент должен уже быть в DOM.
    wp_scripts()->do_items(['admin-bar']);
    return ob_get_clean();
}

function nv_spa_add_body_class($html, $class) {
    if (strpos($html, '<body>') !== false) {
        return str_replace('<body>', '<body class="' . esc_attr($class) . '">', $html);
    }
    return preg_replace_callback('/<body\b([^>]*)class="([^"]*)"/i', function ($m) use ($class) {
        return '<body' . $m[1] . 'class="' . esc_attr($class) . ' ' . $m[2] . '"';
    }, $html, 1);
}

function nv_spa_inject_after_body_open($html, $insert) {
    return preg_replace_callback('/<body\b[^>]*>/i', function ($m) use ($insert) {
        return $m[0] . $insert;
    }, $html, 1);
}
