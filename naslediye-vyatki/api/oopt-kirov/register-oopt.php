<?php
add_action('init', function () {
    register_post_type('oopt', [
        'labels' => [
            'name' => 'Памятники природы',
            'singular_name' => 'Памятник природы',
            'add_new' => 'Добавить памятник',
            'add_new_item' => 'Добавить новый памятник природы',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить памятник природы',
            'new_item' => 'Новый памятник природы',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть памятник природы',
            'search_items' => 'Поиск памятников природы',
            'not_found' => 'Памятники природы не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительский памятник природы'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 17,
        'supports' => ['title', 'editor', 'comments', 'revisions', 'thumbnail', 'post-thumbnails'],
        'taxonomies' => ['oopt_district', 'oopt_profile', 'oopt_species'],
        'menu_icon' => 'dashicons-palmtree',
        'has_archive' => true
    ]);

    register_taxonomy('oopt_district', 'oopt', [
        'labels' => [
            'name' => 'Районы',
            'singular_name' => 'Район',
            'search_items' => 'Поиск районов',
            'all_items' => 'Все районы',
            'edit_item' => 'Изменить район',
            'update_item' => 'Обновить район',
            'add_new_item' => 'Добавить новый район',
            'new_item_name' => 'Название нового района',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    register_taxonomy('oopt_profile', 'oopt', [
        'labels' => [
            'name' => 'Профили',
            'singular_name' => 'Профиль',
            'search_items' => 'Поиск профилей',
            'all_items' => 'Все профили',
            'edit_item' => 'Изменить профиль',
            'update_item' => 'Обновить профиль',
            'add_new_item' => 'Добавить новый профиль',
            'new_item_name' => 'Название нового профиля',
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    register_taxonomy('oopt_species', 'oopt', [
        'labels' => [
            'name' => 'Охраняемые виды',
            'singular_name' => 'Охраняемый вид',
            'search_items' => 'Поиск видов',
            'all_items' => 'Все виды',
            'edit_item' => 'Изменить вид',
            'update_item' => 'Обновить вид',
            'add_new_item' => 'Добавить новый вид',
            'new_item_name' => 'Название нового вида',
        ],
        'hierarchical' => false,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);
});

add_action('rest_api_init', function () {
    register_term_meta('oopt_species', 'oopt_species_status', [
        'type' => 'string',
        'single' => true,
        // 'show_in_rest' => true короткой формой добавляет поле только в
        // контексты view/edit — в _embedded['wp:term'] (контекст embed,
        // используется при ?_embed=1) оно тогда не попадает, и SPA пришлось
        // бы делать отдельный запрос к /wp/v2/oopt_species/{id} на каждый
        // вид. Явно указываем embed в schema.context, чтобы статус вида
        // приходил сразу с постом.
        'show_in_rest' => [
            'schema' => [
                'type' => 'string',
                'context' => ['view', 'edit', 'embed'],
            ],
        ],
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $fields = [
        'oopt_coord',
        'oopt_area',
        'oopt_summary',
        'oopt_imgSrc',
        'oopt_color',
    ];

    foreach ($fields as $field) {
        register_rest_field('oopt', $field, [
            'get_callback' => function ($object, $field_name, $request) {
                return get_post_meta($object['id'], $field_name, true);
            },
            'update_callback' => function ($value, $object, $field_name) {
                // См. аналогичный комментарий в register-hero.php: update_callback
                // вызывается только когда ключ реально есть в теле запроса, поэтому
                // явная пустая строка/null — это запрос на очистку поля, а не повод
                // молча ничего не делать (как было с `if (!$value) return 0;`).
                return update_post_meta($object->ID, $field_name, $value ?? '');
            },
            'schema' => null
        ]);
    }
});

add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'oopt') return false;
    return $current_status;
}, 10, 2);

add_action('admin_init', function () {
    add_meta_box(
        'oopt_summary_box',
        'Аннотация',
        'display_oopt_summary_box',
        'oopt',
        'normal',
        'high'
    );
});

add_action('admin_init', function () {
    add_meta_box(
        'oopt_map_box',
        'Карта и параметры',
        'display_oopt_map_box',
        'oopt',
        'normal',
        'high'
    );
});

function display_oopt_summary_box($oopt) {
    $oopt_summary = esc_html(get_post_meta($oopt->ID, 'oopt_summary', true));
    ?>
    <label for="oopt_summary">Краткое описание:</label>
    <textarea style="width: 100%;" id="oopt_summary" name="oopt_summary" rows="5"><?= esc_html($oopt_summary); ?></textarea>
    <?php
}

function display_oopt_map_box($oopt) {
    wp_enqueue_script('YandexMapAPI-oopt-js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=ba0d0589-f903-43e9-b3d3-cc290988b69a&ver=2.1');
    wp_enqueue_script('oopt_yamap_script', plugin_dir_url(__FILE__) . 'js/adminMap.js');
    $oopt_coord = esc_html(get_post_meta($oopt->ID, 'oopt_coord', true));
    $oopt_area = esc_html(get_post_meta($oopt->ID, 'oopt_area', true));
    $oopt_imgSrc = esc_html(get_post_meta($oopt->ID, 'oopt_imgSrc', true));
    $oopt_color = esc_html(get_post_meta($oopt->ID, 'oopt_color', true));
    ?>
    <div id="map" style="width: 100%; height:300px"></div>
    <input type="search" style="width: 100%;margin-top: 12px;text-align: left;" id="oopt_coord-input"
        name="oopt_coord" value="<?= esc_html($oopt_coord); ?>" />

    <label for="oopt_area" style="margin-top: 12px;">Площадь, га:</label>
    <input type="number" step="any" style="width: 100%;" id="oopt_area" name="oopt_area" value="<?= esc_html($oopt_area); ?>" />

    <label for="oopt_imgsrc" style="margin-top: 12px;">Url картинки для карты:</label>
    <input type="text" style="width: 100%;" id="oopt_imgsrc" name="oopt_imgsrc" value="<?= esc_html($oopt_imgSrc); ?>" />

    <label for="oopt_color" style="margin-top: 12px;">Цвет маркера для карты:</label>
    <input type="color" style="width: 100%;" id="oopt_color" name="oopt_color" value="<?= esc_html($oopt_color); ?>" />
    <?php
}

add_action('save_post', function ($oopt_id, $oopt) {
    // См. аналогичный комментарий в register-hero.php: $_POST пуст при REST-
    // запросах, без этой проверки любое REST-обновление поста обнуляло бы все
    // мета-поля памятника природы до пустой строки.
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if ($oopt->post_type == 'oopt') {
        $fields = [
            'oopt_summary',
            'oopt_coord',
            'oopt_area',
            'oopt_imgsrc' => 'oopt_imgSrc',
            'oopt_color',
        ];

        foreach ($fields as $post_key => $meta_key) {
            if (is_int($post_key)) $post_key = $meta_key;
            if (isset($_POST[$post_key]) && $_POST[$post_key] != '') {
                update_post_meta($oopt_id, $meta_key, $_POST[$post_key]);
            } else {
                update_post_meta($oopt_id, $meta_key, '');
            }
        }
    }
}, 10, 2);

add_action('oopt_species_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label for="oopt_species_status">Охранный статус</label>
        <input type="text" name="oopt_species_status" id="oopt_species_status" value="" />
        <p class="description">Например: КККО, Приложение 2 КККО, КК РФ, МСОП</p>
    </div>
    <?php
});

add_action('oopt_species_edit_form_fields', function ($term) {
    $status = get_term_meta($term->term_id, 'oopt_species_status', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="oopt_species_status">Охранный статус</label></th>
        <td>
            <input type="text" name="oopt_species_status" id="oopt_species_status" value="<?= esc_attr($status); ?>" />
            <p class="description">Например: КККО, Приложение 2 КККО, КК РФ, МСОП</p>
        </td>
    </tr>
    <?php
});

add_action('created_oopt_species', function ($term_id) {
    // REST-запросы (создание термина с полем meta.oopt_species_status) сохраняют
    // статус через собственный REST-механизм register_term_meta — этот хук нужен
    // только для формы "Добавить термин" в админке.
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if (isset($_POST['oopt_species_status']) && $_POST['oopt_species_status'] != '') {
        update_term_meta($term_id, 'oopt_species_status', sanitize_text_field($_POST['oopt_species_status']));
    }
});

add_action('edited_oopt_species', function ($term_id) {
    // Без этой проверки любое REST-обновление термина (например, простое
    // переименование без передачи meta) удаляло бы oopt_species_status — та же
    // причина, что и в save_post у register-hero.php/register-oopt.php: $_POST
    // пуст при запросах через REST API.
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if (isset($_POST['oopt_species_status']) && $_POST['oopt_species_status'] != '') {
        update_term_meta($term_id, 'oopt_species_status', sanitize_text_field($_POST['oopt_species_status']));
    } else {
        delete_term_meta($term_id, 'oopt_species_status');
    }
});
