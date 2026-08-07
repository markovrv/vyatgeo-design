<?php
/**
 * Импорт публичного контента с удалённого WP REST API.
 *
 * Добавляет страницу Инструменты → Импорт из API. Содержимое импортируется
 * пошагово через AJAX — виден прогресс по каждому типу данных. Поддерживаются
 * все CPT и таксономии трёх плагинов: attraction, history, artifact-finder.
 */

add_action('admin_menu', 'nv_import_register_page');
add_action('wp_ajax_nv_import_step', 'nv_import_ajax_step');

function nv_import_register_page() {
    add_management_page(
        'Импорт из API',
        'Импорт из API',
        'import',
        'nv-import',
        'nv_import_render_page'
    );
}

// ---------------------------------------------------------------------------
// Хелперы
// ---------------------------------------------------------------------------

function nv_import_fetch($url) {
    $response = wp_remote_get($url, [
        'timeout' => 30,
        'headers' => ['Accept' => 'application/json'],
    ]);
    if (is_wp_error($response)) return null;
    if (wp_remote_retrieve_response_code($response) !== 200) return null;
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

function nv_import_download_image($url, $post_id, $desc = '') {
    if (empty($url)) return false;
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = download_url($url, 30);
    if (is_wp_error($tmp)) return false;
    $filename = basename(parse_url($url, PHP_URL_PATH)) ?: 'image.jpg';
    $file = ['name' => $filename, 'tmp_name' => $tmp];
    $att_id = media_handle_sideload($file, $post_id, $desc);
    if (is_wp_error($att_id)) { @unlink($tmp); return false; }
    set_post_thumbnail($post_id, $att_id);
    return $att_id;
}

function nv_import_site_url($source_api_url) {
    $site = preg_replace('#/wp-json/?.*$#', '', $source_api_url);
    return untrailingslashit($site);
}

function nv_import_fix_content_urls($content, $source_api_url) {
    $site_url = nv_import_site_url($source_api_url);
    // ../../wp-content/ или ../wp-content/ → абсолютный URL источника
    $content = preg_replace(
        '#(src|href)=(["\'])((?:\.\./)*)wp-content/#i',
        '$1=$2' . $site_url . '/wp-content/',
        $content
    );
    // /wp-content/ (абсолютный от корня, не http[s]://) → абсолютный URL источника
    $content = preg_replace(
        '#(src|href)=(["\'])/(?!\/)wp-content/#i',
        '$1=$2' . $site_url . '/wp-content/',
        $content
    );
    return $content;
}

function nv_import_download_content_images($content, $post_id, $source_api_url) {
    $site_url = nv_import_site_url($source_api_url);
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $escaped = preg_quote($site_url . '/wp-content/', '#');
    $pattern = '#(src|href)=(["\'])' . $escaped . '([^"\']+)\\2#i';
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    $map = [];
    foreach ($matches as $m) {
        $full_url = $site_url . '/wp-content/' . $m[3];
        if (isset($map[$full_url])) continue;

        $tmp = download_url($full_url, 30);
        if (is_wp_error($tmp)) continue;

        $filename = basename(parse_url($full_url, PHP_URL_PATH)) ?: 'image.jpg';
        $att_id = media_handle_sideload(['name' => $filename, 'tmp_name' => $tmp], $post_id);
        if (is_wp_error($att_id)) { @unlink($tmp); continue; }

        $local_url = wp_get_attachment_url($att_id);
        if ($local_url) $map[$full_url] = $local_url;
    }

    foreach ($map as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    return $content;
}

function nv_import_post_exists($title, $post_type) {
    return get_page_by_title($title, OBJECT, $post_type) !== null;
}

function nv_import_find_or_create_term($name, $taxonomy) {
    if (empty($name)) return null;
    $existing = get_term_by('name', $name, $taxonomy);
    if ($existing) return $existing->term_id;
    $term = wp_insert_term($name, $taxonomy);
    if (is_wp_error($term)) {
        if ($term->get_error_code() === 'term_exists') {
            $existing = get_term_by('name', $name, $taxonomy);
            return $existing ? $existing->term_id : null;
        }
        return null;
    }
    return $term['term_id'];
}

// ---------------------------------------------------------------------------
// Управление прогрессом (transient)
// ---------------------------------------------------------------------------

function nv_import_get_state() {
    $key = 'nv_import_state_' . get_current_user_id();
    return get_transient($key) ?: null;
}

function nv_import_set_state($state) {
    $key = 'nv_import_state_' . get_current_user_id();
    set_transient($key, $state, 2 * HOUR_IN_SECONDS);
}

function nv_import_clear_state() {
    $key = 'nv_import_state_' . get_current_user_id();
    delete_transient($key);
}

// ---------------------------------------------------------------------------
// Шаги импорта
// ---------------------------------------------------------------------------

/**
 * Импорт терминов таксономии city (города истории).
 */
function nv_import_step_history_cities($source_url, $download_images) {
    $log = []; $created = 0; $errors = 0;
    $cities = nv_import_fetch($source_url . 'history/v1/cities');
    if (!$cities || !is_array($cities)) return ['log' => ['Не удалось получить список городов.'], 'created' => 0, 'errors' => 1];

    foreach ($cities as $city) {
        $slug = $city['slug'] ?? ''; $name = $city['name'] ?? '';
        if (empty($slug) || empty($name)) { $errors++; continue; }

        $existing = get_term_by('slug', $slug, 'city');
        if ($existing) {
            $term_id = $existing->term_id;
        } else {
            $term = wp_insert_term($name, 'city', ['slug' => $slug, 'description' => $city['description'] ?? '']);
            if (is_wp_error($term)) {
                if ($term->get_error_code() === 'term_exists') {
                    $existing = get_term_by('slug', $slug, 'city');
                    if (!$existing) { $errors++; $log[] = "Ошибка: город «{$name}» — {$term->get_error_message()}"; continue; }
                    $term_id = $existing->term_id;
                } else { $errors++; $log[] = "Ошибка: город «{$name}» — {$term->get_error_message()}"; continue; }
            } else { $term_id = $term['term_id']; $created++; }
        }

        $short = $city['short'] ?? ''; if ($short) update_term_meta($term_id, 'city_short', $short);
        $coords = $city['coordinates'] ?? null;
        if ($coords && is_array($coords) && count($coords) === 2) update_term_meta($term_id, 'city_coord', $coords[0] . ', ' . $coords[1]);

        if ($download_images && !empty($city['photo'])) {
            $tmp = download_url($city['photo'], 30);
            if (!is_wp_error($tmp)) {
                $fn = basename(parse_url($city['photo'], PHP_URL_PATH)) ?: 'city-photo.jpg';
                $att_id = media_handle_sideload(['name' => $fn, 'tmp_name' => $tmp], 0, $name);
                if (!is_wp_error($att_id)) update_term_meta($term_id, 'city_photo', $att_id); else @unlink($tmp);
            }
        }
    }
    $log[] = "Города: обработано " . count($cities) . ", создано {$created}, ошибок {$errors}.";
    return ['log' => $log, 'created' => $created, 'errors' => $errors];
}

/**
 * Импорт терминов произвольной таксономии WP.
 */
function nv_import_step_taxonomy($source_url, $taxonomy) {
    $log = []; $created = 0; $errors = 0;
    $terms = nv_import_fetch($source_url . 'wp/v2/' . $taxonomy . '?per_page=100');
    if (!$terms || !is_array($terms)) return ['log' => ["Не удалось получить термины таксономии «{$taxonomy}»."], 'created' => 0, 'errors' => 1];

    foreach ($terms as $t) {
        $slug = $t['slug'] ?? ''; $name = $t['name'] ?? '';
        if (empty($name)) { $errors++; continue; }
        $existing = get_term_by('slug', $slug, $taxonomy);
        if ($existing) continue;
        $term = wp_insert_term($name, $taxonomy, ['slug' => $slug, 'description' => $t['description'] ?? '']);
        if (is_wp_error($term)) {
            if ($term->get_error_code() !== 'term_exists') { $errors++; $log[] = "Ошибка: термин «{$name}» — {$term->get_error_message()}"; }
        } else { $created++; }
    }
    $log[] = "Таксономия «{$taxonomy}»: обработано " . count($terms) . ", создано {$created}, ошибок {$errors}.";
    return ['log' => $log, 'created' => $created, 'errors' => $errors];
}

/**
 * Импорт терминов finding_type (с картинками).
 */
function nv_import_step_finding_types($source_url, $download_images) {
    $log = []; $created = 0; $errors = 0;
    $types = nv_import_fetch($source_url . 'wp/v2/finding_type?per_page=100');
    if (!$types || !is_array($types)) return ['log' => ['Не удалось получить типы находок.'], 'created' => 0, 'errors' => 1];

    foreach ($types as $t) {
        $slug = $t['slug'] ?? ''; $name = $t['name'] ?? '';
        if (empty($name)) { $errors++; continue; }
        $existing = get_term_by('slug', $slug, 'finding_type');
        if ($existing) { $term_id = $existing->term_id; }
        else {
            $term = wp_insert_term($name, 'finding_type', ['slug' => $slug, 'description' => $t['description'] ?? '']);
            if (is_wp_error($term)) {
                if ($term->get_error_code() === 'term_exists') { $existing = get_term_by('slug', $slug, 'finding_type'); if (!$existing) { $errors++; continue; } $term_id = $existing->term_id; }
                else { $errors++; $log[] = "Ошибка: тип «{$name}» — {$term->get_error_message()}"; continue; }
            } else { $term_id = $term['term_id']; $created++; }
        }
        if ($download_images) {
            $img = $t['finding_type-image'] ?? '';
            if ($img) {
                $tmp = download_url($img, 30);
                if (!is_wp_error($tmp)) {
                    $fn = basename(parse_url($img, PHP_URL_PATH)) ?: 'type-image.jpg';
                    $att_id = media_handle_sideload(['name' => $fn, 'tmp_name' => $tmp], 0, $name);
                    if (!is_wp_error($att_id)) update_term_meta($term_id, 'finding_type-image-id', $att_id); else @unlink($tmp);
                }
            }
        }
    }
    $log[] = "Типы находок: обработано " . count($types) . ", создано {$created}, ошибок {$errors}.";
    return ['log' => $log, 'created' => $created, 'errors' => $errors];
}

/**
 * Импорт объектов attraction (пакетный, обрабатывает BATCH_SIZE за вызов).
 */
define('NV_IMPORT_BATCH', 5);

function nv_import_step_attraction_objects($source_url, $from_idx, $download_images, $skip_existing) {
    $log = []; $created = 0; $errors = 0;
    static $all_items = null;
    if ($all_items === null) {
        $all_items = nv_import_fetch($source_url . 'attraction/v1/objects');
        if (!$all_items || !is_array($all_items)) return ['log' => ['Не удалось получить список объектов архитектуры.'], 'created' => 0, 'errors' => 1, 'total' => 0];
    }

    $total = count($all_items);
    $batch = array_slice($all_items, $from_idx, NV_IMPORT_BATCH);

    foreach ($batch as $item) {
        $id = $item['id'] ?? 0; $title = $item['title'] ?? '';
        if (empty($title)) { $errors++; continue; }
        if ($skip_existing && nv_import_post_exists($title, 'attraction')) continue;

        $detail = nv_import_fetch($source_url . 'wp/v2/attraction/' . $id . '?_embed=1');
        if (!$detail) { $errors++; $log[] = "Ошибка: не удалось загрузить объект «{$title}»."; continue; }

        $content = nv_import_fix_content_urls($detail['content']['rendered'] ?? '', $source_url);

        $post_id = wp_insert_post([
            'post_type' => 'attraction', 'post_title' => $title,
            'post_content' => $content, 'post_status' => 'publish',
        ], true);
        if (is_wp_error($post_id)) { $errors++; $log[] = "Ошибка: «{$title}» — {$post_id->get_error_message()}"; continue; }

        $feat_id = null;

        if ($download_images) {
            $feat = $detail['_embedded']['wp:featuredmedia'][0] ?? null;
            if ($feat) $feat_id = nv_import_download_image($feat['source_url'] ?? '', $post_id, $title);
            $updated = nv_import_download_content_images($content, $post_id, $source_url);
            if ($updated !== $content) {
                wp_update_post(['ID' => $post_id, 'post_content' => $updated]);
            }
        }

        update_post_meta($post_id, 'attraction_coord', $detail['attraction_coord'] ?? '');
        update_post_meta($post_id, 'attraction_place', $detail['attraction_place'] ?? '');
        update_post_meta($post_id, 'attraction_color', $detail['attraction_color'] ?? '');
        update_post_meta($post_id, 'attraction_summarize', $detail['attraction_summarize'] ?? '');

        $img_src = $detail['attraction_imgSrc'] ?? '';
        if ($download_images && $feat_id && !empty($img_src)) {
            $local_url = wp_get_attachment_url($feat_id);
            if ($local_url) $img_src = $local_url;
        }
        update_post_meta($post_id, 'attraction_imgSrc', $img_src);
        $created++;
    }

    $done = ($from_idx + count($batch)) >= $total;
    return ['log' => $log, 'created' => $created, 'errors' => $errors, 'total' => $total, 'done' => $done];
}

/**
 * Импорт событий истории (пакетный по городам).
 */
function nv_import_step_history_events($source_url, $from_idx, $download_images, $skip_existing) {
    $log = []; $created = 0; $errors = 0;
    static $all_events = null;
    if ($all_events === null) {
        $all_events = [];
        $cities = nv_import_fetch($source_url . 'history/v1/cities');
        if ($cities && is_array($cities)) {
            foreach ($cities as $city) {
                $slug = $city['slug'] ?? ''; if (empty($slug)) continue;
                $page = 1; $tp = 1;
                do {
                    $data = nv_import_fetch($source_url . 'history/v1/events?city=' . urlencode($slug) . '&page=' . $page . '&per_page=100');
                    if (!$data || !isset($data['events'])) break;
                    foreach ($data['events'] as $e) { $e['_city_slug'] = $slug; $all_events[] = $e; }
                    $tp = $data['pagination']['totalPages'] ?? 1; $page++;
                } while ($page <= $tp);
            }
        }
    }

    $total = count($all_events);
    $batch = array_slice($all_events, $from_idx, NV_IMPORT_BATCH);

    foreach ($batch as $event) {
        $id = $event['id'] ?? 0; $title = $event['title'] ?? ''; $city_slug = $event['_city_slug'] ?? '';
        if (empty($title)) { $errors++; continue; }
        if ($skip_existing && nv_import_post_exists($title, 'history')) continue;

        $detail = nv_import_fetch($source_url . 'wp/v2/history/' . $id . '?_embed=1');
        if (!$detail) { $errors++; $log[] = "Ошибка: не удалось загрузить событие «{$title}»."; continue; }

        $content = nv_import_fix_content_urls($detail['content']['rendered'] ?? '', $source_url);

        $post_id = wp_insert_post([
            'post_type' => 'history', 'post_title' => $title,
            'post_content' => $content, 'post_status' => 'publish',
        ], true);
        if (is_wp_error($post_id)) { $errors++; $log[] = "Ошибка: «{$title}» — {$post_id->get_error_message()}"; continue; }

        if ($city_slug) {
            $ct = get_term_by('slug', $city_slug, 'city');
            if ($ct) wp_set_post_terms($post_id, [$ct->term_id], 'city', false);
        }
        if ($download_images) {
            $feat = $detail['_embedded']['wp:featuredmedia'][0] ?? null;
            if ($feat) nv_import_download_image($feat['source_url'] ?? '', $post_id, $title);
            $updated = nv_import_download_content_images($content, $post_id, $source_url);
            if ($updated !== $content) {
                wp_update_post(['ID' => $post_id, 'post_content' => $updated]);
            }
        }
        $created++;

        update_post_meta($post_id, 'history_date_text', $detail['history_date_text'] ?? '');
        update_post_meta($post_id, 'history_date_value', $detail['history_date_value'] ?? '');
    }

    $done = ($from_idx + count($batch)) >= $total;
    return ['log' => $log, 'created' => $created, 'errors' => $errors, 'total' => $total, 'done' => $done];
}

/**
 * Импорт находок (finding, пакетный).
 */
function nv_import_step_finding_objects($source_url, $from_idx, $download_images, $skip_existing) {
    $log = []; $created = 0; $errors = 0;
    static $all_findings = null;
    if ($all_findings === null) {
        $all_findings = [];
        $page = 1; $tp = 1;
        do {
            $data = nv_import_fetch($source_url . 'findings/v1/findings/?page=' . $page . '&per_page=100');
            if (!$data || !isset($data['findings'])) break;
            foreach ($data['findings'] as $f) $all_findings[] = $f;
            $tp = $data['total_pages'] ?? 1; $page++;
        } while ($page <= $tp);
    }

    $total = count($all_findings);
    $batch = array_slice($all_findings, $from_idx, NV_IMPORT_BATCH);

    $tax_map = [
        'finding_material' => 'materials', 'finding_origin' => 'origin',
        'finding_receipt_time' => 'receipt_time', 'finding_creation_time' => 'creation_time',
        'finding_type' => 'type',
    ];

    foreach ($batch as $f) {
        $title = $f['title'] ?? '';
        if (empty($title)) { $errors++; continue; }
        if ($skip_existing && nv_import_post_exists($title, 'finding')) continue;

        $content = nv_import_fix_content_urls($f['content'] ?? '', $source_url);

        $post_id = wp_insert_post([
            'post_type' => 'finding', 'post_title' => $title,
            'post_content' => $content, 'post_excerpt' => $f['excerpt'] ?? '', 'post_status' => 'publish',
        ], true);
        if (is_wp_error($post_id)) { $errors++; $log[] = "Ошибка: «{$title}» — {$post_id->get_error_message()}"; continue; }

        if ($download_images) {
            if (!empty($f['thumbnail'])) nv_import_download_image($f['thumbnail'], $post_id, $title);
            $meta_imgs = [];
            foreach ((array)($f['additional_images'] ?? []) as $img_url) {
                if (empty($img_url)) continue;
                $tmp = download_url($img_url, 30); if (is_wp_error($tmp)) continue;
                $fn = basename(parse_url($img_url, PHP_URL_PATH)) ?: 'finding-image.jpg';
                $att_id = media_handle_sideload(['name' => $fn, 'tmp_name' => $tmp], $post_id);
                if (!is_wp_error($att_id)) $meta_imgs[] = $att_id; else @unlink($tmp);
            }
            if ($meta_imgs) update_post_meta($post_id, 'finding_additional_images', $meta_imgs);
            $updated = nv_import_download_content_images($content, $post_id, $source_url);
            if ($updated !== $content) {
                wp_update_post(['ID' => $post_id, 'post_content' => $updated]);
            }
        }

        update_post_meta($post_id, 'finding_dimensions', $f['dimensions'] ?? '');
        update_post_meta($post_id, 'finding_cat_id', $f['cat_id'] ?? '');
        update_post_meta($post_id, 'finding_functionality', $f['functionality'] ?? '');
        update_post_meta($post_id, 'finding_features', $f['features'] ?? '');

        foreach ($tax_map as $tax => $field) {
            $names = $f[$field] ?? [];
            $ids = [];
            foreach ((array)$names as $n) { $tid = nv_import_find_or_create_term($n, $tax); if ($tid) $ids[] = $tid; }
            if ($ids) wp_set_post_terms($post_id, $ids, $tax, false);
        }
        $created++;
    }

    $done = ($from_idx + count($batch)) >= $total;
    return ['log' => $log, 'created' => $created, 'errors' => $errors, 'total' => $total, 'done' => $done];
}

// ---------------------------------------------------------------------------
// AJAX-обработчик
// ---------------------------------------------------------------------------

function nv_import_ajax_step() {
    if (!current_user_can('import')) wp_die(-1);
    check_ajax_referer('nv_import_ajax', 'nonce');

    // Отмена: клиент прислал cancel=1
    if (!empty($_POST['cancel'])) {
        $state = nv_import_get_state();
        if ($state) { $state['cancelled'] = true; nv_import_set_state($state); }
        nv_import_clear_state();
        wp_send_json_success(['done' => true, 'log' => ['Импорт отменён.'], 'current_step' => '']);
        return;
    }

    // Новый импорт: если передан steps — всегда сбрасываем старое состояние
    $steps_raw = $_POST['steps'] ?? '';
    if (!empty($steps_raw)) {
        nv_import_clear_state();
    }

    $state = nv_import_get_state();
    if (!$state) {
        $enabled = array_values(array_filter(is_array($steps_raw) ? $steps_raw : explode(',', $steps_raw)));
        if (empty($enabled)) { wp_send_json_error('Ни один шаг не выбран.'); return; }

        $source_url = trailingslashit(esc_url_raw($_POST['source_url'] ?? ''));
        if (empty($source_url)) { wp_send_json_error('Укажите URL источника.'); return; }

        $download_images = !empty($_POST['download_images']);
        $skip_existing = !empty($_POST['skip_existing']);

        $queue = array_values($enabled);
        $state = [
            'source_url' => $source_url, 'download_images' => $download_images,
            'skip_existing' => $skip_existing, 'queue' => $queue, 'queue_idx' => 0,
            'from_idx' => 0, 'log' => [], 'total_created' => 0, 'total_errors' => 0,
            'cancelled' => false,
        ];
    }

    if ($state['cancelled']) { nv_import_clear_state(); wp_send_json_success(['done' => true, 'log' => array_merge($state['log'], ['Импорт отменён.']), 'current_step' => '']); return; }

    $current_step = $state['queue'][$state['queue_idx']] ?? null;
    if (!$current_step) { nv_import_clear_state(); wp_send_json_success(['done' => true, 'log' => $state['log'], 'current_step' => '']); return; }

    $su = $state['source_url']; $di = $state['download_images']; $se = $state['skip_existing'];

    switch ($current_step) {
        case 'history_cities':      $result = nv_import_step_history_cities($su, $di); $batch_done = true; break;
        case 'finding_types':       $result = nv_import_step_finding_types($su, $di); $batch_done = true; break;
        case 'finding_material':
        case 'finding_origin':
        case 'finding_receipt_time':
        case 'finding_creation_time': $result = nv_import_step_taxonomy($su, $current_step); $batch_done = true; break;
        case 'attraction_objects':  $result = nv_import_step_attraction_objects($su, $state['from_idx'], $di, $se); $batch_done = $result['done'] ?? false; break;
        case 'history_events':      $result = nv_import_step_history_events($su, $state['from_idx'], $di, $se); $batch_done = $result['done'] ?? false; break;
        case 'finding_objects':     $result = nv_import_step_finding_objects($su, $state['from_idx'], $di, $se); $batch_done = $result['done'] ?? false; break;
        default: $result = ['log' => ["Неизвестный шаг: {$current_step}"], 'errors' => 1]; $batch_done = true;
    }

    $state['log'] = array_merge($state['log'], $result['log'] ?? []);
    $state['total_created'] += $result['created'] ?? 0;
    $state['total_errors'] += $result['errors'] ?? 0;

    if ($batch_done) {
        $state['queue_idx']++;
        $state['from_idx'] = 0;
    } else {
        $state['from_idx'] += NV_IMPORT_BATCH;
    }

    $next_step = $state['queue'][$state['queue_idx']] ?? null;
    if (!$next_step) { nv_import_clear_state(); wp_send_json_success(['done' => true, 'log' => $state['log'], 'current_step' => '']); return; }

    nv_import_set_state($state);

    $total = $result['total'] ?? 0;
    $processed = $batch_done ? $total : min($state['from_idx'], $total);
    wp_send_json_success([
        'done' => false,
        'current_step' => $current_step,
        'total' => $total,
        'processed' => $processed,
    ]);
}

// ---------------------------------------------------------------------------
// Страница админки
// ---------------------------------------------------------------------------

function nv_import_render_page() {
    $step_labels = [
        'history_cities'           => 'Города (city)',
        'finding_types'            => 'Типы находок (finding_type)',
        'finding_material'         => 'Материалы (finding_material)',
        'finding_origin'           => 'Происхождение (finding_origin)',
        'finding_receipt_time'     => 'Время поступления (finding_receipt_time)',
        'finding_creation_time'    => 'Время создания (finding_creation_time)',
        'attraction_objects'       => 'Объекты архитектуры',
        'history_events'           => 'События истории',
        'finding_objects'          => 'Находки этнографии',
    ];

    $default_url = esc_attr('https://testsite.vyatgeo.ru/wp-json/');
    $ajax_nonce  = wp_create_nonce('nv_import_ajax');
    ?>
    <div class="wrap">
        <h1>Импорт контента из API</h1>
        <p>Копирует публичный контент с удалённого WordPress-сайта в текущий. Импорт идёт пошагово — сначала таксономии, затем объекты. Ход выполнения виден в реальном времени.</p>

        <form id="nv-import-form" method="post" onsubmit="return false;">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nv_source_url">URL API источника</label></th>
                    <td>
                        <input type="url" id="nv_source_url" value="<?php echo $default_url; ?>" class="regular-text" required />
                        <p class="description">Базовый URL WordPress REST API (с /wp-json/ на конце).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Содержимое для импорта</th>
                    <td>
                        <div id="nv-import-tree">
                            <div class="nv-plugin-group">
                                <label class="nv-plugin-toggle">
                                    <input type="checkbox" class="nv-group-check" data-group="attraction" checked />
                                    <strong>Архитектура</strong> <code>(attraction)</code>
                                </label>
                                <ul class="nv-plugin-items">
                                    <li><label><input type="checkbox" name="steps[]" value="attraction_objects" data-group="attraction" checked /> Объекты (достопримечательности)</label></li>
                                </ul>
                            </div>
                            <div class="nv-plugin-group">
                                <label class="nv-plugin-toggle">
                                    <input type="checkbox" class="nv-group-check" data-group="history" checked />
                                    <strong>Города и история</strong> <code>(history)</code>
                                </label>
                                <ul class="nv-plugin-items">
                                    <li><label><input type="checkbox" name="steps[]" value="history_cities" data-group="history" checked /> Таксономия «Города»</label></li>
                                    <li><label><input type="checkbox" name="steps[]" value="history_events" data-group="history" checked /> События (лента времени)</label></li>
                                </ul>
                            </div>
                            <div class="nv-plugin-group">
                                <label class="nv-plugin-toggle">
                                    <input type="checkbox" class="nv-group-check" data-group="finding" checked />
                                    <strong>Этнография</strong> <code>(finding)</code>
                                </label>
                                <ul class="nv-plugin-items">
                                    <li><label><input type="checkbox" name="steps[]" value="finding_types" data-group="finding" checked /> Таксономия «Типы находок»</label></li>
                                    <li><label><input type="checkbox" name="steps[]" value="finding_material" data-group="finding" checked /> Таксономия «Материалы»</label></li>
                                    <li><label><input type="checkbox" name="steps[]" value="finding_origin" data-group="finding" checked /> Таксономия «Происхождение»</label></li>
                                    <li><label><input type="checkbox" name="steps[]" value="finding_receipt_time" data-group="finding" checked /> Таксономия «Время поступления»</label></li>
                                    <li><label><input type="checkbox" name="steps[]" value="finding_creation_time" data-group="finding" checked /> Таксономия «Время создания»</label></li>
                                    <li><label><input type="checkbox" name="steps[]" value="finding_objects" data-group="finding" checked /> Находки</label></li>
                                </ul>
                            </div>
                        </div>
                        <p class="description">Таксономии импортируются первыми, чтобы объекты могли на них ссылаться.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Настройки</th>
                    <td>
                        <label><input type="checkbox" id="nv_skip_existing" checked /> Пропускать существующие записи (по точному совпадению заголовка)</label><br />
                        <label><input type="checkbox" id="nv_download_images" checked /> Скачивать изображения (медленно для больших наборов)</label>
                    </td>
                </tr>
            </table>

            <p id="nv-import-controls">
                <button type="button" id="nv-start-import" class="button button-primary">Начать импорт</button>
                <button type="button" id="nv-cancel-import" class="button" style="display:none;">Отменить</button>
            </p>
        </form>

        <div id="nv-import-progress" style="display:none; margin-top: 20px;">
            <h3 id="nv-import-step-label"></h3>
            <div style="background:#e0e0e0; border-radius:4px; height:24px; max-width:600px;">
                <div id="nv-import-bar" style="background:#3C7A8C; height:100%; width:0%; border-radius:4px; transition: width 0.3s;"></div>
            </div>
            <p id="nv-import-count" style="margin:4px 0;"></p>
            <div id="nv-import-log" style="max-height:300px; overflow-y:auto; background:#fff; border:1px solid #ccd0d4; padding:8px 12px; font-size:13px; line-height:1.5; max-width:600px;"></div>
        </div>
    </div>

    <style>
    #nv-import-tree ul { margin: 4px 0 4px 20px; list-style: none; }
    #nv-import-tree .nv-plugin-group { margin-bottom: 8px; }
    #nv-import-tree .nv-plugin-toggle { cursor: pointer; }
    .nv-log-error { color: #dc3232; }
    .nv-log-warn { color: #c27e3a; }
    </style>

    <script>
    (function() {
        const form = document.getElementById('nv-import-form');
        const startBtn = document.getElementById('nv-start-import');
        const cancelBtn = document.getElementById('nv-cancel-import');
        const progressDiv = document.getElementById('nv-import-progress');
        const stepLabel = document.getElementById('nv-import-step-label');
        const bar = document.getElementById('nv-import-bar');
        const count = document.getElementById('nv-import-count');
        const logDiv = document.getElementById('nv-import-log');
        let cancelled = false;
        let running = false;

        const STEP_LABELS = <?php echo json_encode($step_labels, JSON_UNESCAPED_UNICODE); ?>;
        const AJAX_URL = '<?php echo admin_url("admin-ajax.php"); ?>';
        const NONCE = '<?php echo $ajax_nonce; ?>';

        document.querySelectorAll('.nv-group-check').forEach(cb => {
            cb.addEventListener('change', function() {
                document.querySelectorAll('input[data-group="' + this.dataset.group + '"]').forEach(c => c.checked = this.checked);
            });
        });

        function log(msg, cls) {
            const p = document.createElement('div');
            p.textContent = msg;
            if (cls) p.className = cls;
            logDiv.appendChild(p);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function sendStep(data) {
            const fd = new FormData();
            for (let k in data) fd.append(k, data[k]);
            fd.append('action', 'nv_import_step');
            fd.append('nonce', NONCE);
            return fetch(AJAX_URL, { method: 'POST', body: fd }).then(r => r.json());
        }

        function resetUI() {
            running = false;
            startBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'none';
            cancelBtn.disabled = false;
            document.querySelectorAll('#nv-import-form input').forEach(el => el.disabled = false);
        }

        async function runImport() {
            if (running) { log('Импорт уже выполняется. Нажмите «Отменить» для остановки.', 'nv-log-warn'); return; }

            const steps = [];
            document.querySelectorAll('input[name="steps[]"]:checked').forEach(cb => steps.push(cb.value));
            if (steps.length === 0) { alert('Выберите хотя бы один тип контента.'); return; }

            cancelled = false;
            running = true;
            logDiv.innerHTML = '';
            progressDiv.style.display = 'block';
            startBtn.style.display = 'none';
            cancelBtn.style.display = 'inline-block';
            document.querySelectorAll('#nv-import-form input').forEach(el => el.disabled = true);

            let payload = {
                source_url: document.getElementById('nv_source_url').value.trim(),
                download_images: document.getElementById('nv_download_images').checked ? '1' : '',
                skip_existing: document.getElementById('nv_skip_existing').checked ? '1' : '',
                steps: steps.join(','),
            };

            let done = false;
            while (!done && !cancelled) {
                let resp;
                try {
                    resp = await sendStep(payload);
                    payload = {};
                } catch (e) {
                    log('Ошибка сети при обращении к серверу.', 'nv-log-error');
                    break;
                }

                if (!resp.success) {
                    log(resp.data || 'Неизвестная ошибка.', 'nv-log-error');
                    break;
                }

                done = resp.data.done;
                if (done) {
                    if (resp.data.log && resp.data.log.length) {
                        resp.data.log.forEach(m => log(m, m.startsWith('Ошибка') ? 'nv-log-error' : ''));
                    }
                    log('Импорт завершён.', '');
                    bar.style.width = '100%';
                    break;
                }

                const step = resp.data.current_step;
                const total = resp.data.total || 0;
                const processed = resp.data.processed || 0;
                const pct = total > 0 ? Math.round(processed / total * 100) : 0;

                stepLabel.textContent = STEP_LABELS[step] || step;
                bar.style.width = pct + '%';
                count.textContent = processed + ' / ' + total;
            }

            resetUI();
        }

        startBtn.addEventListener('click', runImport);

        cancelBtn.addEventListener('click', async function() {
            cancelled = true;
            cancelBtn.disabled = true;
            log('Отмена...', 'nv-log-warn');
            // Отправляем сигнал отмены на сервер
            try { await sendStep({ cancel: '1' }); } catch (e) {}
        });
    })();
    </script>
    <?php
}
