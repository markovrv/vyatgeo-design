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

add_action('template_redirect', 'nv_spa_bootstrap', 0);
add_action('customize_register', 'nv_spa_customize_register');

function nv_spa_bootstrap() {
    // Служебные виртуальные эндпоинты самого WP не трогаем.
    if (is_robots() || is_favicon() || is_feed() || is_trackback() || is_customize_preview()) {
        return;
    }

    $request_path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if (nv_spa_serve_static_asset($request_path)) {
        exit;
    }

    nv_spa_render_app();
    exit;
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
    $html = str_replace('</head>', nv_spa_customizer_script() . '</head>', $html);
    echo $html;
}
