<?php
if (!class_exists(class: 'WP_REST_Request')) {
    require_once(ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php');
}

add_action('rest_api_init', function () {
    register_rest_route('attraction/v1', '/objects', [
        'methods' => 'GET',
        'callback' => 'get_attraction_objects',
        'permission_callback' => '__return_true'
    ]);

    // Соседний объект (Предыдущий/Следующий) — по возрастанию ID; у attraction
    // нет своих таксономий (см. api/docs/attraction.md), фильтровать не по чему.
    register_rest_route('attraction/v1', '/objects/(?P<id>\d+)/adjacent', [
        'methods' => 'GET',
        'callback' => 'get_attraction_adjacent',
        'permission_callback' => '__return_true',
    ]);

    // Ближайшие по координатам объекты — расстояние по формуле гаверсинуса.
    // Радиус поиска (км) обязателен параметром max_distance — хранится
    // константой на клиенте (window.__ATTRACTION_NEARBY_RADIUS_KM__ в
    // index.html) и приходит с каждым запросом, а не зашит на сервере.
    register_rest_route('attraction/v1', '/objects/(?P<id>\d+)/nearby', [
        'methods' => 'GET',
        'callback' => 'get_attraction_nearby',
        'permission_callback' => '__return_true',
        'args' => [
            'max_distance' => [
                'required' => true,
                'validate_callback' => function ($param) {
                    return is_numeric($param) && $param > 0;
                },
            ],
        ],
    ]);
});

function get_attraction_objects() {
    $args = [
        'post_type' => 'attraction',
        'posts_per_page' => -1, // Получаем все записи сразу
        'post_status' => 'publish',
    ];

    $query = new WP_Query($args);
    $objects = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Получаем мета-данные одним запросом
            $meta = get_post_meta($post_id);

            $thumbnail_id = get_post_thumbnail_id($post_id);
            $imgSrc = $meta['attraction_imgSrc'][0] ?? '';

            if (empty($imgSrc) && $thumbnail_id) {
                // Если imgSrc пустой, получаем URL thumbnail
                $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                // Сохраняем URL миниатюры в метаполе imgSrc
                update_post_meta($post_id, 'imgSrc', $imgSrc);
            }
            
            $coordinates = explode(', ', $meta['attraction_coord'][0] ?? '0, 0');
            
            $objects[] = [
                "id" => $post_id,
                "coordinates" => [
                    (float) ($coordinates[0] ?? 0),
                    (float) ($coordinates[1] ?? 0),
                ],
                "title" => get_the_title(),
                "place" => $meta['attraction_place'][0] ?? '',
                "imgSrc" => $imgSrc,
                "color" => $meta['attraction_color'][0] ?? '',
                "summarize" => '',
                "content" => '',
                "img" => $thumbnail_id,
            ];
        }
        wp_reset_postdata();
    }

    return array_reverse($objects);
}

function get_attraction_coordinates($post_id) {
    $raw = get_post_meta($post_id, 'attraction_coord', true);
    $parts = explode(', ', $raw ?: '0, 0');

    return [
        'lat' => (float) ($parts[0] ?? 0),
        'lng' => (float) ($parts[1] ?? 0),
    ];
}

// Общий формат для карточки объекта в блоках навигации (Предыдущий/Следующий,
// Объекты рядом) — только то, что нужно для превью-ссылки, не полная запись.
function format_attraction_nav_item($post_id) {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $imgSrc = get_post_meta($post_id, 'attraction_imgSrc', true);

    if (empty($imgSrc) && $thumbnail_id) {
        $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
    }

    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'place' => get_post_meta($post_id, 'attraction_place', true),
        'imgSrc' => $imgSrc,
        // ID вложения — как и в массовом /objects, чтобы клиент мог при
        // необходимости подтянуть более крупный вариант через /wp/v2/media
        // (см. useAttractionThumbnails на фронте), как для карточек каталога.
        'img' => $thumbnail_id,
    ];
}

// Предыдущий/Следующий объект — по возрастанию ID, без категорий (их у
// attraction нет, см. api/docs/attraction.md).
function get_attraction_adjacent($request) {
    $id = intval($request['id']);

    $ids = get_posts([
        'post_type' => 'attraction',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
    ]);

    $position = array_search($id, $ids);
    $result = ['prev' => null, 'next' => null];

    if ($position !== false) {
        if ($position > 0) {
            $result['prev'] = format_attraction_nav_item($ids[$position - 1]);
        }
        if ($position < count($ids) - 1) {
            $result['next'] = format_attraction_nav_item($ids[$position + 1]);
        }
    }

    return rest_ensure_response($result);
}

// Расстояние между двумя точками на сфере (км), формула гаверсинуса, R = 6371.
function attraction_haversine_distance_km($lat1, $lng1, $lat2, $lng2) {
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// Ближайшие по координатам объекты в радиусе max_distance (км, обязательный
// параметр с клиента) — не более 4, отсортированы по расстоянию по возрастанию.
function get_attraction_nearby($request) {
    $id = intval($request['id']);
    $post = get_post($id);

    if (!$post || $post->post_type !== 'attraction') {
        return new WP_Error('attraction_not_found', 'Объект не найден', ['status' => 404]);
    }

    $max_distance = (float) $request->get_param('max_distance');
    $origin = get_attraction_coordinates($id);

    if ($origin['lat'] === 0.0 && $origin['lng'] === 0.0) {
        return rest_ensure_response(['nearby' => []]);
    }

    $candidate_ids = get_posts([
        'post_type' => 'attraction',
        'post_status' => 'publish',
        'post__not_in' => [$id],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    $scored = [];
    foreach ($candidate_ids as $candidate_id) {
        $coord = get_attraction_coordinates($candidate_id);
        if ($coord['lat'] === 0.0 && $coord['lng'] === 0.0) {
            continue;
        }

        $distance = attraction_haversine_distance_km($origin['lat'], $origin['lng'], $coord['lat'], $coord['lng']);
        if ($distance <= $max_distance) {
            $scored[] = ['id' => $candidate_id, 'distance' => $distance];
        }
    }

    usort($scored, function ($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    $nearby = array_map(function ($item) {
        $data = format_attraction_nav_item($item['id']);
        $data['distanceKm'] = round($item['distance'], 3);
        return $data;
    }, array_slice($scored, 0, 4));

    return rest_ensure_response(['nearby' => $nearby]);
}