<?php
/**
 * Плагин «Памятники археологии Кировской области»
 *
 * Регистрирует CPT archeology с таксономиями archeology_era (эпоха)
 * и archeology_type (тип памятника), мета-поля для координат, картинки,
 * аннотации, и REST API для SPA-фронтенда.
 */

add_action('init', function () {
    register_post_type('archeology', [
        'labels' => [
            'name' => 'Памятники археологии',
            'singular_name' => 'Памятник',
            'add_new' => 'Добавить памятник',
            'add_new_item' => 'Добавить новый памятник',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить памятник',
            'new_item' => 'Новый памятник',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть памятник',
            'search_items' => 'Поиск памятников',
            'not_found' => 'Памятники не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительский памятник'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 18,
        'supports' => ['title', 'editor', 'comments', 'revisions', 'thumbnail', 'post-thumbnails'],
        'taxonomies' => ['archeology_era', 'archeology_type'],
        'menu_icon' => 'dashicons-shield',
        'has_archive' => true
    ]);

    register_taxonomy('archeology_era', 'archeology', [
        'labels' => [
            'name' => 'Эпохи',
            'singular_name' => 'Эпоха',
            'search_items' => 'Поиск эпох',
            'all_items' => 'Все эпохи',
            'edit_item' => 'Изменить эпоху',
            'update_item' => 'Обновить эпоху',
            'add_new_item' => 'Добавить новую эпоху',
            'new_item_name' => 'Название новой эпохи',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    register_taxonomy('archeology_type', 'archeology', [
        'labels' => [
            'name' => 'Типы памятников',
            'singular_name' => 'Тип',
            'search_items' => 'Поиск типов',
            'all_items' => 'Все типы',
            'edit_item' => 'Изменить тип',
            'update_item' => 'Обновить тип',
            'add_new_item' => 'Добавить новый тип',
            'new_item_name' => 'Название нового типа',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
});

// Мета-поля в REST API
add_action('rest_api_init', function () {
    $fields = [
        'archeology_coord',
        'archeology_place',
        'archeology_imgSrc',
        'archeology_color',
        'archeology_summarize',
    ];

    foreach ($fields as $field) {
        register_rest_field('archeology', $field, [
            'get_callback' => function ($object, $field_name, $request) {
                return get_post_meta($object['id'], $field_name, true);
            },
            'update_callback' => function ($value, $object, $field_name) {
                return update_post_meta($object->ID, $field_name, $value ?? '');
            },
            'schema' => null
        ]);
    }
});

// Отключение Gutenberg
add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'archeology') return false;
    return $current_status;
}, 10, 2);

// Метабоксы в админке
add_action('admin_init', function () {
    add_meta_box(
        'archeology_summarize',
        'Аннотация памятника',
        'display_archeology_summarize',
        'archeology',
        'normal',
        'high'
    );
});

add_action('admin_init', function () {
    add_meta_box(
        'archeology_map_box',
        'Координаты памятника',
        'display_archeology_map_box',
        'archeology',
        'normal',
        'high'
    );
});

function display_archeology_summarize($post) {
    $summarize = esc_html(get_post_meta($post->ID, 'archeology_summarize', true));
    ?>
    <textarea style="width: 100%;" id="archeology_summarize" name="archeology_summarize" rows="4"><?= esc_html($summarize); ?></textarea>
    <?php
}

function display_archeology_map_box($post) {
    wp_enqueue_script('YandexMapAPI-alt-js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=ba0d0589-f903-43e9-b3d3-cc290988b69a&ver=2.1');
    wp_enqueue_script('archeology_map_script', plugin_dir_url(__FILE__) . 'js/adminMap.js');
    $coord = esc_html(get_post_meta($post->ID, 'archeology_coord', true));
    $place = esc_html(get_post_meta($post->ID, 'archeology_place', true));
    $imgSrc = esc_html(get_post_meta($post->ID, 'archeology_imgSrc', true));
    $color = esc_html(get_post_meta($post->ID, 'archeology_color', true));
    ?>
    <div id="map" style="width: 100%; height:300px"></div>
    <input type="search" style="width: 100%;margin-top: 12px;text-align: left;" id="archeology_coord-input"
        name="archeology_coord" value="<?= esc_html($coord); ?>" />
    <label for="archeology_place" style="margin-top: 12px;">Расположение памятника:</label>
    <input type="text" style="width: 100%;text-align: left;" id="archeology_place"
        name="archeology_place" value="<?= esc_html($place); ?>" />
    <label for="archeology_imgsrc" style="margin-top: 12px;">Url картинки для карты:</label>
    <input type="text" style="width: 100%;text-align: left;" id="archeology_imgsrc"
        name="archeology_imgsrc" value="<?= esc_html($imgSrc); ?>" />
    <label for="archeology_color" style="margin-top: 12px;">Цвет маркера для карты:</label>
    <input type="color" style="width: 100%;text-align: left;" id="archeology_color"
        name="archeology_color" value="<?= esc_html($color); ?>" />
    <?php
}

// Сохранение мета-полей из админки
add_action('save_post', function ($post_id, $post) {
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    if ($post->post_type == 'archeology') {
        $fields = ['archeology_summarize', 'archeology_coord', 'archeology_place',
                   'archeology_imgsrc' => 'archeology_imgSrc', 'archeology_color'];

        foreach ($fields as $post_key => $meta_key) {
            if (is_int($post_key)) $post_key = $meta_key;
            if (isset($_POST[$post_key]) && $_POST[$post_key] != '') {
                update_post_meta($post_id, $meta_key, $_POST[$post_key]);
            } else {
                update_post_meta($post_id, $meta_key, '');
            }
        }
    }
}, 10, 2);
