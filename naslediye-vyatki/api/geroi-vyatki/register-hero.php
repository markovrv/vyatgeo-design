<?php
add_action('init', function () {
    register_post_type('hero', [
        'labels' => [
            'name' => 'Герои',
            'singular_name' => 'Герой',
            'add_new' => 'Добавить героя',
            'add_new_item' => 'Добавить нового героя',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить героя',
            'new_item' => 'Новый герой',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть героя',
            'search_items' => 'Поиск героев',
            'not_found' => 'Герои не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительский герой'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 16,
        'supports' => ['title', 'editor', 'comments', 'revisions', 'thumbnail', 'post-thumbnails'],
        'taxonomies' => ['hero_status'],
        'menu_icon' => 'dashicons-awards',
        'has_archive' => true
    ]);

    register_taxonomy('hero_status', 'hero', [
        'labels' => [
            'name' => 'Статус персоналии',
            'singular_name' => 'Статус',
            'search_items' => 'Поиск статусов',
            'all_items' => 'Все статусы',
            'edit_item' => 'Изменить статус',
            'update_item' => 'Обновить статус',
            'add_new_item' => 'Добавить новый статус',
            'new_item_name' => 'Название нового статуса',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
});

add_action('rest_api_init', function () {
    $fields = [
        'hero_birth_date',
        'hero_death_date',
        'hero_birth_place',
        'hero_death_place',
        'hero_feat_description',
        'hero_awards',
        'hero_profession',
        'hero_coord',
        'hero_connection',
        'hero_imgSrc',
        'hero_color',
    ];

    foreach ($fields as $field) {
        register_rest_field('hero', $field, [
            'get_callback' => function ($object, $field_name, $request) {
                return get_post_meta($object['id'], $field_name, true);
            },
            'update_callback' => function ($value, $object, $field_name) {
                // register_rest_field вызывает update_callback только если ключ
                // реально присутствует в теле запроса — значит, явно переданная
                // пустая строка/null означает "очистить поле", а не "оставить
                // как есть". Раньше `if (!$value) return 0;` тихо игнорировал
                // такие запросы, и поле было невозможно очистить через REST.
                return update_post_meta($object->ID, $field_name, $value ?? '');
            },
            'schema' => null
        ]);
    }
});

add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'hero') return false;
    return $current_status;
}, 10, 2);

add_action('admin_init', function () {
    add_meta_box(
        'hero_bio_box',
        'Подвиг и биография',
        'display_hero_bio_box',
        'hero',
        'normal',
        'high'
    );
});

add_action('admin_init', function () {
    add_meta_box(
        'hero_map_box',
        'Место и координаты',
        'display_hero_map_box',
        'hero',
        'normal',
        'high'
    );
});

function display_hero_bio_box($hero) {
    $hero_feat_description = esc_html(get_post_meta($hero->ID, 'hero_feat_description', true));
    $hero_awards = esc_html(get_post_meta($hero->ID, 'hero_awards', true));
    $hero_profession = esc_html(get_post_meta($hero->ID, 'hero_profession', true));
    $hero_connection = esc_html(get_post_meta($hero->ID, 'hero_connection', true));
    ?>
    <label for="hero_feat_description">Описание подвига, заслуг, вклада:</label>
    <textarea style="width: 100%;" id="hero_feat_description" name="hero_feat_description" rows="5"><?= esc_html($hero_feat_description); ?></textarea>

    <label for="hero_awards" style="margin-top: 12px; display: block;">Перечень наград (ордена, медали, звания):</label>
    <textarea style="width: 100%;" id="hero_awards" name="hero_awards" rows="3"><?= esc_html($hero_awards); ?></textarea>

    <label for="hero_profession" style="margin-top: 12px; display: block;">Профессия, деятельность в мирное время:</label>
    <input type="text" style="width: 100%;" id="hero_profession" name="hero_profession" value="<?= esc_html($hero_profession); ?>" />

    <label for="hero_connection" style="margin-top: 12px; display: block;">Характер связи с Вятским краем:</label>
    <textarea style="width: 100%;" id="hero_connection" name="hero_connection" rows="3"><?= esc_html($hero_connection); ?></textarea>
    <?php
}

function display_hero_map_box($hero) {
    wp_enqueue_script('YandexMapAPI-hero-js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=ba0d0589-f903-43e9-b3d3-cc290988b69a&ver=2.1');
    wp_enqueue_script('hero_yamap_script', plugin_dir_url(__FILE__) . 'js/adminMap.js');
    $hero_coord = esc_html(get_post_meta($hero->ID, 'hero_coord', true));
    $hero_birth_place = esc_html(get_post_meta($hero->ID, 'hero_birth_place', true));
    $hero_death_place = esc_html(get_post_meta($hero->ID, 'hero_death_place', true));
    $hero_birth_date = esc_html(get_post_meta($hero->ID, 'hero_birth_date', true));
    $hero_death_date = esc_html(get_post_meta($hero->ID, 'hero_death_date', true));
    $hero_imgSrc = esc_html(get_post_meta($hero->ID, 'hero_imgSrc', true));
    $hero_color = esc_html(get_post_meta($hero->ID, 'hero_color', true));
    ?>
    <div id="map" style="width: 100%; height:300px"></div>
    <input type="search" style="width: 100%;margin-top: 12px;text-align: left;" id="hero_coord-input"
        name="hero_coord" value="<?= esc_html($hero_coord); ?>" />

    <label for="hero_birth_date" style="margin-top: 12px;">Дата рождения:</label>
    <input type="text" style="width: 100%;" id="hero_birth_date" name="hero_birth_date" value="<?= esc_html($hero_birth_date); ?>" />

    <label for="hero_death_date" style="margin-top: 12px;">Дата смерти:</label>
    <input type="text" style="width: 100%;" id="hero_death_date" name="hero_death_date" value="<?= esc_html($hero_death_date); ?>" />

    <label for="hero_birth_place" style="margin-top: 12px;">Место рождения:</label>
    <input type="text" style="width: 100%;" id="hero_birth_place" name="hero_birth_place" value="<?= esc_html($hero_birth_place); ?>" />

    <label for="hero_death_place" style="margin-top: 12px;">Место смерти:</label>
    <input type="text" style="width: 100%;" id="hero_death_place" name="hero_death_place" value="<?= esc_html($hero_death_place); ?>" />

    <label for="hero_imgsrc" style="margin-top: 12px;">Url картинки для карты:</label>
    <input type="text" style="width: 100%;" id="hero_imgsrc" name="hero_imgsrc" value="<?= esc_html($hero_imgSrc); ?>" />

    <label for="hero_color" style="margin-top: 12px;">Цвет маркера для карты:</label>
    <input type="color" style="width: 100%;" id="hero_color" name="hero_color" value="<?= esc_html($hero_color); ?>" />
    <?php
}

add_action('save_post', function ($hero_id, $hero) {
    // При запросах через REST API (создание/обновление постов из Python-скрипта
    // импорта, SPA и т.п.) $_POST всегда пуст — значения полей приходят через
    // register_rest_field и сохраняются его update_callback независимо от этого
    // хука. Без этой проверки любое REST-обновление поста (даже не трогающее
    // эти поля вовсе) обнуляло бы все мета-поля героя до пустой строки.
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if ($hero->post_type == 'hero') {
        $fields = [
            'hero_feat_description',
            'hero_awards',
            'hero_profession',
            'hero_connection',
            'hero_coord',
            'hero_birth_date',
            'hero_death_date',
            'hero_birth_place',
            'hero_death_place',
            'hero_imgsrc' => 'hero_imgSrc',
            'hero_color',
        ];

        foreach ($fields as $post_key => $meta_key) {
            if (is_int($post_key)) $post_key = $meta_key;
            if (isset($_POST[$post_key]) && $_POST[$post_key] != '') {
                update_post_meta($hero_id, $meta_key, $_POST[$post_key]);
            } else {
                update_post_meta($hero_id, $meta_key, '');
            }
        }
    }
}, 10, 2);
