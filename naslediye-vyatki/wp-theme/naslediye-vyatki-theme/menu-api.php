<?php
/**
 * Публичный REST-эндпоинт для получения навигационного меню SPA.
 *
 * GET nv/v1/menu/{location}
 *
 * Возвращает пункты меню (плоский список с parentId) или пустой массив,
 * если локация не назначена ни одному меню. Публичный доступ без
 * аутентификации — как у всех кастомных REST-роутов проекта.
 */
require_once ABSPATH . WPINC . '/nav-menu.php';

add_action('rest_api_init', function () {

    register_rest_route('nv/v1', '/menu/(?P<location>[a-z0-9_]+)', [
        'methods'             => 'GET',
        'callback'            => 'nv_spa_get_menu',
        'permission_callback' => '__return_true',
    ]);
});

function nv_spa_get_menu($request) {
    $location = $request['location'];

    $locations = get_nav_menu_locations();
    $menu_id   = $locations[$location] ?? 0;

    if (!$menu_id) {
        return rest_ensure_response([]);
    }

    $items = wp_get_nav_menu_items($menu_id);

    if (!$items || is_wp_error($items)) {
        return rest_ensure_response([]);
    }

    $result = [];
    foreach ($items as $item) {
        $url = wp_strip_all_tags($item->url);

        $result[] = [
            'id'       => (int) $item->ID,
            'title'    => wp_strip_all_tags($item->title),
            'url'      => $url,
            'parentId' => (int) $item->menu_item_parent,
            'order'    => (int) $item->menu_order,
            'target'   => $item->target ?: '',
        ];
    }

    return rest_ensure_response($result);
}
