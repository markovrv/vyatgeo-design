<?php
// Кастомный REST API endpoint для поиска и фильтрации
add_action('rest_api_init', function () {
    register_rest_route('findings/v1', '/findings/', [
        'methods' => 'GET',
        'callback' => 'get_findings_filtered',
        'permission_callback' => '__return_true',
    ]);
    
    // POST endpoint для обновления данных находки
    register_rest_route('findings/v1', '/findings/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_finding_data',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'args' => [
            'id' => [
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param) && get_post($param) !== null;
                }
            ],
        ],
    ]);

    // Соседние находки (Предыдущая/Следующая), опционально в рамках одной категории
    register_rest_route('findings/v1', '/findings/(?P<id>\d+)/adjacent', [
        'methods' => 'GET',
        'callback' => 'get_finding_adjacent',
        'permission_callback' => '__return_true',
    ]);

    // Похожие находки — по совпадению таксономий и текста
    register_rest_route('findings/v1', '/findings/(?P<id>\d+)/similar', [
        'methods' => 'GET',
        'callback' => 'get_finding_similar',
        'permission_callback' => '__return_true',
    ]);
});

function get_findings_filtered($request) {
    $params = $request->get_params();
    
    $args = [
        'post_type' => 'finding',
        'post_status' => 'publish',
        'posts_per_page' => isset($params['per_page']) ? intval($params['per_page']) : 12,
        'paged' => isset($params['page']) ? intval($params['page']) : 1,
    ];

    // Точечная выборка одной записи по ID — используется страницей одного
    // экспоната, чтобы не грузить всю коллекцию ради одного объекта
    if (!empty($params['id'])) {
        $args['post__in'] = [intval($params['id'])];
        $args['posts_per_page'] = 1;
        unset($args['paged']);
    }

    // Фильтрация по таксономиям
    $tax_query = [];
    
    if (!empty($params['finding_material'])) {
        $tax_query[] = [
            'taxonomy' => 'finding_material',
            'field' => 'slug',
            'terms' => $params['finding_material']
        ];
    }
    
    if (!empty($params['finding_origin'])) {
        $tax_query[] = [
            'taxonomy' => 'finding_origin',
            'field' => 'slug',
            'terms' => $params['finding_origin']
        ];
    }
    
    if (!empty($params['finding_creation_time'])) {
        $tax_query[] = [
            'taxonomy' => 'finding_creation_time',
            'field' => 'slug',
            'terms' => $params['finding_creation_time']
        ];
    }

    if (!empty($params['finding_type'])) {
        $tax_query[] = [
            'taxonomy' => 'finding_type',
            'field' => 'slug',
            'terms' => $params['finding_type']
        ];
    }

    if (!empty($params['finding_receipt_time'])) {
        $tax_query[] = [
            'taxonomy' => 'finding_receipt_time',
            'field' => 'slug',
            'terms' => $params['finding_receipt_time']
        ];
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Поиск по названию
    if (!empty($params['search'])) {
        $args['s'] = sanitize_text_field($params['search']);
    }
    
    $query = new WP_Query($args);
    $findings = [];

    foreach ($query->posts as $post) {
        $findings[] = format_finding_response($post);
    }

    return rest_ensure_response([
        'findings' => $findings,
        'total' => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'current_page' => $args['paged']
    ]);
}

// Общий формат ответа для одной находки — переиспользуется списком, точечной
// выборкой по ID и блоком "Похожие находки"
function format_finding_response($post) {
    $additional_images = get_post_meta($post->ID, 'finding_additional_images', true) ?: [];
    $image_urls = [];

    foreach ($additional_images as $image_id) {
        $image_urls[] = wp_get_attachment_url($image_id);
    }

    return [
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'excerpt' => $post->post_excerpt,
        'thumbnail' => get_the_post_thumbnail_url($post->ID, 'large'),
        'dimensions' => get_post_meta($post->ID, 'finding_dimensions', true),
        'cat_id' => get_post_meta($post->ID, 'finding_cat_id', true),
        'functionality' => get_post_meta($post->ID, 'finding_functionality', true),
        'features' => get_post_meta($post->ID, 'finding_features', true),
        'additional_images' => $image_urls,
        'materials' => wp_get_post_terms($post->ID, 'finding_material', ['fields' => 'names']),
        'origin' => wp_get_post_terms($post->ID, 'finding_origin', ['fields' => 'names']),
        'creation_time' => wp_get_post_terms($post->ID, 'finding_creation_time', ['fields' => 'names']),
        'receipt_time' => wp_get_post_terms($post->ID, 'finding_receipt_time', ['fields' => 'names']),
        'type' => wp_get_post_terms($post->ID, 'finding_type', ['fields' => 'names'])
    ];
}

// Предыдущая/Следующая находка — по возрастанию ID, опционально только внутри
// одной категории (finding_type), если она передана параметром
function get_finding_adjacent($request) {
    $id = intval($request['id']);
    $type_slug = $request->get_param('finding_type');

    $args = [
        'post_type' => 'finding',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
    ];

    if (!empty($type_slug)) {
        $args['tax_query'] = [[
            'taxonomy' => 'finding_type',
            'field' => 'slug',
            'terms' => $type_slug,
        ]];
    }

    $ids = get_posts($args);
    $position = array_search($id, $ids);

    $result = ['prev' => null, 'next' => null];

    if ($position !== false) {
        if ($position > 0) {
            $result['prev'] = format_finding_nav_item($ids[$position - 1]);
        }
        if ($position < count($ids) - 1) {
            $result['next'] = format_finding_nav_item($ids[$position + 1]);
        }
    }

    return rest_ensure_response($result);
}

function format_finding_nav_item($post_id) {
    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium') ?: get_the_post_thumbnail_url($post_id, 'large'),
    ];
}

// Похожие находки — кандидаты собираются по пересечению таксономий
// (тип/материал/время создания), затем ранжируются по числу совпадающих
// признаков (таксономии + общие значимые слова в названии/описании).
// Возвращается не более 4, только с score > 0 — лучше меньше, чем "для галочки".
function get_finding_similar($request) {
    $id = intval($request['id']);
    $post = get_post($id);

    if (!$post || $post->post_type !== 'finding') {
        return new WP_Error('finding_not_found', 'Находка не найдена', ['status' => 404]);
    }

    $type_ids = wp_get_post_terms($id, 'finding_type', ['fields' => 'ids']);
    $material_ids = wp_get_post_terms($id, 'finding_material', ['fields' => 'ids']);
    $origin_ids = wp_get_post_terms($id, 'finding_origin', ['fields' => 'ids']);
    $creation_ids = wp_get_post_terms($id, 'finding_creation_time', ['fields' => 'ids']);
    $receipt_ids = wp_get_post_terms($id, 'finding_receipt_time', ['fields' => 'ids']);

    // Кандидатов ищем по трём наиболее содержательным таксономиям (тип,
    // материал, время создания) — происхождение и время поступления слишком
    // "широкие" (например, у одного термина происхождения бывают сотни находок),
    // их используем только как доп.баллы при ранжировании уже найденных кандидатов
    $tax_query = ['relation' => 'OR'];
    if ($type_ids) $tax_query[] = ['taxonomy' => 'finding_type', 'field' => 'term_id', 'terms' => $type_ids];
    if ($material_ids) $tax_query[] = ['taxonomy' => 'finding_material', 'field' => 'term_id', 'terms' => $material_ids];
    if ($creation_ids) $tax_query[] = ['taxonomy' => 'finding_creation_time', 'field' => 'term_id', 'terms' => $creation_ids];

    if (count($tax_query) <= 1) {
        return rest_ensure_response(['similar' => []]);
    }

    $candidates = get_posts([
        'post_type' => 'finding',
        'post_status' => 'publish',
        'post__not_in' => [$id],
        'posts_per_page' => 100,
        'tax_query' => $tax_query,
    ]);

    $current_words = get_finding_significant_words($post->post_title . ' ' . $post->post_content);

    $scored = [];
    foreach ($candidates as $candidate) {
        $score = 0;

        if ($type_ids && count(array_intersect(wp_get_post_terms($candidate->ID, 'finding_type', ['fields' => 'ids']), $type_ids))) {
            $score += 5;
        }
        $score += 2 * count(array_intersect(wp_get_post_terms($candidate->ID, 'finding_material', ['fields' => 'ids']), $material_ids));
        $score += 2 * count(array_intersect(wp_get_post_terms($candidate->ID, 'finding_creation_time', ['fields' => 'ids']), $creation_ids));
        $score += count(array_intersect(wp_get_post_terms($candidate->ID, 'finding_origin', ['fields' => 'ids']), $origin_ids));
        $score += count(array_intersect(wp_get_post_terms($candidate->ID, 'finding_receipt_time', ['fields' => 'ids']), $receipt_ids));

        $candidate_words = get_finding_significant_words($candidate->post_title . ' ' . $candidate->post_content);
        $score += count(array_intersect($current_words, $candidate_words));

        if ($score > 0) {
            $scored[] = ['post' => $candidate, 'score' => $score];
        }
    }

    usort($scored, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $top = array_slice($scored, 0, 4);
    $similar = array_map(function ($item) {
        return format_finding_response($item['post']);
    }, $top);

    return rest_ensure_response(['similar' => $similar]);
}

function get_finding_significant_words($text) {
    $text = mb_strtolower(strip_tags($text), 'UTF-8');
    preg_match_all('/[а-яё]{4,}/u', $text, $matches);
    return array_unique($matches[0]);
}

function update_finding_data($request) {
    $finding_id = $request['id'];
    $params = $request->get_params();
    
    // Проверяем, существует ли находка
    $finding = get_post($finding_id);
    if (!$finding || $finding->post_type !== 'finding') {
        return new WP_Error('finding_not_found', 'Находка не найдена', ['status' => 404]);
    }
    
    $response_data = [];
    
    // Ожидаем JSON массив из двух объектов
    if (!isset($params['taxonomies']) || !isset($params['meta_fields'])) {
        return new WP_Error('invalid_data', 'Неверный формат данных. Ожидается массив с объектами taxonomies и meta_fields', ['status' => 400]);
    }
    
    $taxonomies_data = $params['taxonomies'];
    $meta_fields_data = $params['meta_fields'];
    
    // Обновление таксономий
    if (is_array($taxonomies_data) && !empty($taxonomies_data)) {
        foreach ($taxonomies_data as $taxonomy => $term_ids) {
            // Проверяем, что таксономия существует
            if (!taxonomy_exists($taxonomy)) {
                $response_data['taxonomy_errors'][$taxonomy] = 'Таксономия не существует';
                continue;
            }
            
            // Проверяем, что значения - это массив ID
            if (!is_array($term_ids)) {
                $response_data['taxonomy_errors'][$taxonomy] = 'Значения таксономии должны быть массивом ID';
                continue;
            }
            
            // Валидируем ID терминов
            $valid_term_ids = [];
            foreach ($term_ids as $term_id) {
                if (!is_numeric($term_id)) {
                    $response_data['taxonomy_errors'][$taxonomy] = 'ID терминов должны быть числовыми';
                    continue 2; // Переходим к следующей таксономии
                }
                
                $term = get_term($term_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $valid_term_ids[] = (int)$term_id;
                }
            }
            
            // Обновляем таксономию
            $result = wp_set_object_terms($finding_id, $valid_term_ids, $taxonomy);
            
            if (!is_wp_error($result)) {
                $response_data['taxonomies_updated'][$taxonomy] = true;
            } else {
                $response_data['taxonomy_errors'][$taxonomy] = $result->get_error_message();
            }
        }
    }
    
    // Обновление метаполей
    if (is_array($meta_fields_data) && !empty($meta_fields_data)) {
        foreach ($meta_fields_data as $meta_key => $meta_value) {
            // Проверяем, что метаполе разрешено для обновления
            $allowed_meta_fields = [
                'finding_dimensions',
                'finding_cat_id', 
                'finding_functionality',
                'finding_features',
                'finding_additional_images'
            ];
            
            if (!in_array($meta_key, $allowed_meta_fields)) {
                $response_data['meta_errors'][$meta_key] = 'Метаполе не разрешено для обновления';
                continue;
            }
            
            // Особенная обработка для дополнительных изображений
            if ($meta_key === 'finding_additional_images') {
                if (is_array($meta_value)) {
                    $image_ids = array_map('intval', $meta_value);
                    $update_result = update_post_meta($finding_id, $meta_key, $image_ids);
                } else {
                    $update_result = update_post_meta($finding_id, $meta_key, []);
                }
            } else {
                // Обычные метаполя
                $update_result = update_post_meta($finding_id, $meta_key, sanitize_text_field($meta_value));
            }
            
            if ($update_result !== false) {
                $response_data['meta_updated'][$meta_key] = true;
            } else {
                $response_data['meta_errors'][$meta_key] = 'Ошибка при обновлении метаполя';
            }
        }
    }
    
    $response_data['success'] = true;
    $response_data['message'] = 'Данные находки успешно обновлены';
    $response_data['finding_id'] = $finding_id;
    
    return rest_ensure_response($response_data);
}