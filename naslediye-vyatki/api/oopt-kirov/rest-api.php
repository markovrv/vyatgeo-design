<?php
if (!class_exists('WP_REST_Request')) {
    require_once(ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php');
}

add_action('rest_api_init', function () {
    register_rest_route('oopt/v1', '/objects', [
        'methods' => 'GET',
        'callback' => 'get_oopt_objects',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('oopt/v1', '/objects/(?P<id>\d+)/adjacent', [
        'methods' => 'GET',
        'callback' => 'get_oopt_adjacent',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('oopt/v1', '/objects/(?P<id>\d+)/nearby', [
        'methods' => 'GET',
        'callback' => 'get_oopt_nearby',
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

function get_oopt_objects() {
    $args = [
        'post_type' => 'oopt',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];

    $query = new WP_Query($args);
    $objects = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $meta = get_post_meta($post_id);

            $thumbnail_id = get_post_thumbnail_id($post_id);
            $imgSrc = $meta['oopt_imgSrc'][0] ?? '';

            if (empty($imgSrc) && $thumbnail_id) {
                $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
            }

            $coordinates = explode(', ', $meta['oopt_coord'][0] ?? '0, 0');

            $district_terms = get_the_terms($post_id, 'oopt_district');
            $place = '';
            if ($district_terms && !is_wp_error($district_terms)) {
                $place = $district_terms[0]->name;
            }

            $objects[] = [
                'id' => $post_id,
                'coordinates' => [
                    (float) ($coordinates[0] ?? 0),
                    (float) ($coordinates[1] ?? 0),
                ],
                'title' => get_the_title(),
                'place' => $place,
                'imgSrc' => $imgSrc,
                'color' => $meta['oopt_color'][0] ?? '',
                'summarize' => $meta['oopt_summary'][0] ?? '',
                'content' => '',
                'img' => $thumbnail_id,
            ];
        }
        wp_reset_postdata();
    }

    return array_reverse($objects);
}

function get_oopt_coordinates($post_id) {
    $raw = get_post_meta($post_id, 'oopt_coord', true);
    $parts = explode(', ', $raw ?: '0, 0');

    return [
        'lat' => (float) ($parts[0] ?? 0),
        'lng' => (float) ($parts[1] ?? 0),
    ];
}

function format_oopt_nav_item($post_id) {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $imgSrc = get_post_meta($post_id, 'oopt_imgSrc', true);

    if (empty($imgSrc) && $thumbnail_id) {
        $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
    }

    $district_terms = get_the_terms($post_id, 'oopt_district');
    $place = '';
    if ($district_terms && !is_wp_error($district_terms)) {
        $place = $district_terms[0]->name;
    }

    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'place' => $place,
        'imgSrc' => $imgSrc,
        'img' => $thumbnail_id,
    ];
}

function get_oopt_adjacent($request) {
    $id = intval($request['id']);

    $ids = get_posts([
        'post_type' => 'oopt',
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
            $result['prev'] = format_oopt_nav_item($ids[$position - 1]);
        }
        if ($position < count($ids) - 1) {
            $result['next'] = format_oopt_nav_item($ids[$position + 1]);
        }
    }

    return rest_ensure_response($result);
}

function oopt_haversine_distance_km($lat1, $lng1, $lat2, $lng2) {
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function get_oopt_nearby($request) {
    $id = intval($request['id']);
    $post = get_post($id);

    if (!$post || $post->post_type !== 'oopt') {
        return new WP_Error('oopt_not_found', 'Памятник природы не найден', ['status' => 404]);
    }

    $max_distance = (float) $request->get_param('max_distance');
    $origin = get_oopt_coordinates($id);

    if ($origin['lat'] === 0.0 && $origin['lng'] === 0.0) {
        return rest_ensure_response(['nearby' => []]);
    }

    $candidate_ids = get_posts([
        'post_type' => 'oopt',
        'post_status' => 'publish',
        'post__not_in' => [$id],
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    $scored = [];
    foreach ($candidate_ids as $candidate_id) {
        $coord = get_oopt_coordinates($candidate_id);
        if ($coord['lat'] === 0.0 && $coord['lng'] === 0.0) {
            continue;
        }

        $distance = oopt_haversine_distance_km($origin['lat'], $origin['lng'], $coord['lat'], $coord['lng']);
        if ($distance <= $max_distance) {
            $scored[] = ['id' => $candidate_id, 'distance' => $distance];
        }
    }

    usort($scored, function ($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    $nearby = array_map(function ($item) {
        $data = format_oopt_nav_item($item['id']);
        $data['distanceKm'] = round($item['distance'], 3);
        return $data;
    }, array_slice($scored, 0, 4));

    return rest_ensure_response(['nearby' => $nearby]);
}
