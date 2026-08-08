<?php
// Регистрируем Объект
add_action('init', function () {
    register_post_type('attraction', [
        'labels' => [
            'name' => 'Объекты',
            'singular_name' => 'Объект',
            'add_new' => 'Добавить объект',
            'add_new_item' => 'Добавить новый объект',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить объект',
            'new_item' => 'Новый объект',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть объект',
            'search_items' => 'Поиск объектов',
            'not_found' => 'Объекты не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительский объект'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 15,
        'supports' => ['title', 'editor', 'comments', 'revisions', 'thumbnail', 'post-thumbnails'],
        'taxonomies' => [],
        'menu_icon' => "dashicons-location-alt",
        'has_archive' => true
    ]);
});

// Добавление мета-полей в стандартный апи
add_action('rest_api_init', function () {
    $fields = [
        'attraction_coord',
        'attraction_place',
        'attraction_imgSrc',
        'attraction_color',
		'attraction_summarize'
    ];

    foreach ($fields as $field) {
        register_rest_field('attraction', $field, [
            'get_callback' => function ($object, $field_name, $request) {
                return get_post_meta($object["id"], $field_name, true);
            },
            'update_callback' => function ($value, $object, $field_name) {
                if (!$value)  return 0;
                return update_post_meta($object->ID, $field_name, $value);
            },
            'schema' => null
        ]);
    }
});

// отключение редактора gutenberg в редакторе геоточек
add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'attraction')
        return false;
    return $current_status;
}, 10, 2);

// Добавляем панели редактирования своих полей в админку
add_action('admin_init', function () {
    add_meta_box(
        id: 'attraction_summarize',
        title: 'Аннотация объекта',
        callback: 'display_attraction_summarize',
        screen: 'attraction',
        context: 'normal',
        priority: 'high'
    );
});

add_action('admin_init', function () {
    add_meta_box(
        id: 'attraction_map_box',
        title: 'Координаты объекта',
        callback: 'display_attraction_map_box',
        screen: 'attraction',
        context: 'normal',
        priority: 'high'
    );
});

function display_attraction_summarize($attraction) {
    $attraction_summarize =  esc_html(get_post_meta($attraction->ID, 'attraction_summarize', true));
    ?>
    <textarea style="width: 100%;" id="attraction_summarize" name="attraction_summarize"><?= esc_html($attraction_summarize); ?></textarea>
    <?php
}

function display_attraction_map_box($attraction) {
    wp_enqueue_script('my_yamap_script', plugin_dir_url(file: __FILE__) . '/js/adminMap.js');
    $attraction_coord =  esc_html(get_post_meta($attraction->ID, 'attraction_coord', true));
    $attraction_place =  esc_html(get_post_meta($attraction->ID, 'attraction_place', true));
    $attraction_imgSrc = esc_html(get_post_meta($attraction->ID, 'attraction_imgSrc', true));
    $attraction_color =  esc_html(get_post_meta($attraction->ID, 'attraction_color', true));
    ?>
    <div id="map" style="width: 100%; height:300px"></div>
    <input type="search" style="width: 100%;margin-top: 12px;text-align: left;" id="attraction_coord-input"
        name="attraction_coord" value="<?= esc_html($attraction_coord); ?>" />
    <label for="attraction_place" style="margin-top: 12px;">Расположение объекта:</label>
    <input type="text" style="width: 100%;text-align: left;" id="attraction_place"
        name="attraction_place" value="<?= esc_html($attraction_place); ?>" />
    <label for="attraction_imgsrc" style="margin-top: 12px;">Url картинки для карты:</label>
    <input type="text" style="width: 100%;text-align: left;" id="attraction_imgsrc"
        name="attraction_imgsrc" value="<?= esc_html($attraction_imgSrc); ?>" />
    <label for="attraction_color" style="margin-top: 12px;">Цвет маркера для карты:</label>
    <input type="color" style="width: 100%;text-align: left;" id="attraction_color"
        name="attraction_color" value="<?= esc_html($attraction_color); ?>" />
    <?php
}

// сохранение своих полей в админке
add_action('save_post', function ($attraction_id, $attraction) {
    global $wpdb;
    if ($attraction->post_type == 'attraction') {
        if (isset ($_POST['attraction_summarize']) && $_POST['attraction_summarize'] != '') {
            update_post_meta($attraction_id, 'attraction_summarize', $_POST['attraction_summarize']);
        } else {
            update_post_meta($attraction_id, 'attraction_summarize', '');
        }
        if (isset ($_POST['attraction_coord']) && $_POST['attraction_coord'] != '') {
            update_post_meta($attraction_id, 'attraction_coord', $_POST['attraction_coord']);
        } else {
            update_post_meta($attraction_id, 'attraction_coord', '');
        }
        if (isset ($_POST['attraction_place']) && $_POST['attraction_place'] != '') {
            update_post_meta($attraction_id, 'attraction_place', $_POST['attraction_place']);
        } else {
            update_post_meta($attraction_id, 'attraction_place', '');
        }
        if (isset ($_POST['attraction_imgsrc']) && $_POST['attraction_imgsrc'] != '') {
            update_post_meta($attraction_id, 'attraction_imgSrc', $_POST['attraction_imgsrc']);
        } else {
            update_post_meta($attraction_id, 'attraction_imgSrc', '');
        }
        if (isset ($_POST['attraction_color']) && $_POST['attraction_color'] != '') {
            update_post_meta($attraction_id, 'attraction_color', $_POST['attraction_color']);
        } else {
            update_post_meta($attraction_id, 'attraction_color', '');
        }
    }
}, 10, 2);

// замена заголовка архива
add_action('blocksy:hero:after', function () {
    if (is_archive() && is_post_type_archive('attraction') && !is_admin()):
        ?> 
        <style>
            .my-menu {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 12px;
            }
        </style>
            <div class="hero-section" data-type="type-1">
                <a href="../attraction-about/" class="my-menu">О разделе</a>&nbsp;
                <span style="color: gray">:</span>&nbsp;
                <a href="../attraction-map/" class="my-menu">Карта объектов</a>
                <header class="entry-header">
                    <h1 class="page-title" title="Архитектура и скульптура г. Кирова" itemprop="headline">
                        <span class="ct-title-label">Архитектура и скульптура г. Кирова: </span> Прошлое и современность
                    </h1>
                </header>
            </div>
        <?php
    endif;
});