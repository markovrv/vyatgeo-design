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