<?php
// Регистрируем объект Работа
add_action('init', function () {
    register_post_type('geopoints', [
        'labels' => [
            'name' => 'Работы',
            'singular_name' => 'Работа',
            'add_new' => 'Добавить работу',
            'add_new_item' => 'Добавить новую работу',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить работу',
            'new_item' => 'Новая работа',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть работу',
            'search_items' => 'Поиск работ',
            'not_found' => 'Работы не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительская работа'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 15,
        'supports' => ['title', 'editor', 'comments', 'revisions', 'thumbnail', 'post-thumbnails'],
        'taxonomies' => [],
        'menu_icon' => "dashicons-hammer",
        'has_archive' => true
    ]);
});

// Добавление мета-полей в стандартный апи
add_action('rest_api_init', function () {
    $fields = ['address', 'area', 'begin_time', 'end_time', 'geopoints_coord', 'geopoints_pages', 'imgSrc'];

    foreach ($fields as $field) {
        register_rest_field('geopoints', $field, [
            'get_callback' => function ($object) use ($field) {
                return get_post_meta($object["id"], $field, true);
            },
            'update_callback' => function ($value, $object) use ($field) {
                if ($field === 'area' || $field === 'begin_time' || $field === 'end_time') {
                    if (!$value || !is_numeric($value)) return;
                } elseif (!$value) {
                    return;
                }
                return update_post_meta($object->ID, $field, $value);
            },
            'schema' => null
        ]);
    }
});

// отключение редактора gutenberg в редакторе геоточек
add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'geopoints')
        return false;
    return $current_status;
}, 10, 2);

// Регистрируем таксономию Руководитель
add_action('init', function () {

    $labels_tag = [
        'name' => 'Руководители',
        'singular_name' => 'Руководитель',
        'menu_name' => 'Руководители работ',
        'search_items' => 'Поиск по руководителям',
        'all_items' => 'Все руководители',
        'edit_item' => 'Редактировать руководителя',
        'view_item' => 'Посмотреть страницу руководителя',
        'update_item' => 'Сохранить руководителя',
        'add_new_item' => 'Добавить нового руководителя',
        'new_item_name' => 'Имя нового руководителя',
        'not_found' => 'Руководителей не найдено',
        'back_to_items' => 'Назад на страницу руководителей',
    ];
    $args_tag = [
        'labels' => $labels_tag,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'show_in_quick_edit' => false,
        'meta_box_cb' => false,
    ];
    register_taxonomy('headman', ['geopoints'], $args_tag);
});

// Добавляем руководителю характеристики Цвет маркера и Фото
add_action('headman_add_form_fields', function ($taxonomy) {
    ?>
    <div class="form-field">
        <label for="headman-color">Цвет маркера</label>
        <input type="color" name="headman-color" id="headman-color" />
    </div>
    <div class="form-field term-group">
        <label for="headman-image-id">Изображение</label>
        <input type="hidden" id="headman-image-id" name="headman-image-id" class="custom_media_url" value="">
        <div id="headman-image-wrapper"></div>
        <p>
            <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="Прикрепить" />
            <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="Открепить" />
        </p>
        <small><code>[headman_photo]</code> в описании отображает картинку на странице руководителя.</small>
    </div>
    <?php
});

add_action('headman_edit_form_fields', function ($term, $taxonomy) {
    $color = get_term_meta($term->term_id, 'headman-color', true);
    $image_id = get_term_meta($term->term_id, 'headman-image-id', true);
    ?>
    <tr class="form-field">
        <th><label for="headman-color">Цвет маркера</label></th>
        <td><input name="headman-color" id="headman-color" type="color" value="<?= esc_attr($color); ?>" /></td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="headman-image-id">Изображение</label></th>
        <td>
            <input type="hidden" id="headman-image-id" name="headman-image-id" value="<?php echo esc_attr($image_id); ?>">
            <div id="headman-image-wrapper">
                <?php if ($image_id) : ?>
                    <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                <?php endif; ?>
            </div>
            <p>
                <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="Прикрепить" />
                <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="Открепить" />
            </p>
            <p>
                <small><code>[headman_photo]</code> в описании отображает картинку на странице руководителя.</small>
            </p>
        </td>
    </tr>
    <?php
}, 10, 2);

add_action('created_headman', 'headman_save_term_fields');
add_action('edited_headman', 'headman_save_term_fields');

function headman_save_term_fields($term_id) {
    if (isset($_POST['headman-color'])) {
        update_term_meta($term_id, 'headman-color', sanitize_text_field($_POST['headman-color']));
    } else {
        delete_term_meta($term_id, 'headman-color');
    }
    
    if (isset($_POST['headman-image-id']) && !empty($_POST['headman-image-id'])) {
        update_term_meta($term_id, 'headman-image-id', absint($_POST['headman-image-id']));
    } else {
        delete_term_meta($term_id, 'headman-image-id');
    }
}

// создаем новую колонку
add_filter('manage_edit-headman_columns', function ($columns) {
    $columns['headman-color'] = 'Цвет';
    $columns['headman-image'] = 'Фото';
    return $columns;
}, 4);

// заполняем колонку данными
add_filter('manage_headman_custom_column', function ($content, $column_name, $term_id) {
    if ($column_name === 'headman-color') {
        return "<div style='width:50px;height:20px;background-color:" . get_term_meta($term_id, 'headman-color', true) . "' />";
    }
    
    if ($column_name === 'headman-image') {
        $image_id = get_term_meta($term_id, 'headman-image-id', true);
        if ($image_id) {
            return wp_get_attachment_image($image_id, 'thumbnail');
        }
    }
    
    return $content;
}, 10, 3);

// добавление полей в rest api
add_action('rest_api_init', function () {
    register_rest_field('headman', 'headman-color', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_term_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_term_meta($object->term_id, $field_name, $value);
        },
        'schema' => null
    ]);
    
    register_rest_field('headman', 'headman-image', [
        'get_callback' => function ($object, $field_name, $request) {
            $image_id = get_term_meta($object["id"], 'headman-image-id', true);
            return $image_id ? wp_get_attachment_url($image_id) : null;
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_term_meta($object->term_id, 'headman-image-id', $value);
        },
        'schema' => null
    ]);
});

// Добавляем скрипты для работы медиабиблиотеки
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_media();
    
    wp_add_inline_script('jquery', '
        jQuery(($) => {
            const $imageId = $("#headman-image-id");
            const $imageWrapper = $("#headman-image-wrapper");
            const $description = $("#description");
            
            // Обработчик для кнопки добавления/изменения изображения
            $("body").on("click", ".ct_tax_media_button", (e) => {
                e.preventDefault();
                
                const frame = wp.media({
                    title: "Выберите изображение",
                    button: { text: "Использовать это изображение" },
                    multiple: false
                });
                
                frame.on("select", () => {
                    const attachment = frame.state().get("selection").first().toJSON();
                    const thumbUrl = attachment.sizes?.thumbnail?.url || attachment.url;
                    
                    $imageId.val(attachment.id);
                    $description.val("[headman_photo]");
                    $imageWrapper.html(`
                        <img 
                            src="${thumbUrl}" 
                            width="150" 
                            height="150"
                            class="attachment-thumbnail size-thumbnail"
                            alt="${attachment.alt}"
                            loading="lazy"
                        />
                    `);
                });
                
                frame.open();
            });
            
            // Обработчик для кнопки удаления изображения
            $("body").on("click", ".ct_tax_media_remove", (e) => {
                e.preventDefault();
                $imageId.val("");
                $description.val("");
                $imageWrapper.empty();
            });
        });
    ');
});

// Регистрируем шорткод для отображения фото руководителя в теме
add_shortcode('headman_photo', function($atts) {
    // Получаем текущий термин таксономии
    $term = get_queried_object();
    
    if (!$term || !is_a($term, 'WP_Term') || $term->taxonomy !== 'headman') {
        return '';
    }
    
    // Получаем ID изображения из метаполя
    $image_id = get_term_meta($term->term_id, 'headman-image-id', true);
    
    if (!$image_id) {
        return '';
    }
    
    // Получаем URL изображения
    $image_url = wp_get_attachment_image_url($image_id, 'medium');
    
    if (!$image_url) {
        return '';
    }
    
    // Формируем HTML для изображения
    return sprintf(
        '<div class="headman-photo">%s</div>',
        wp_get_attachment_image($image_id, 'medium', false, [
            'class' => 'headman-photo-img',
            'loading' => 'lazy',
            'alt' => sprintf(__('Фото руководителя %s'), $term->name)
        ])
    );
});

// Добавляем фильтр для обработки описания таксономии
add_filter('term_description', 'do_shortcode');

// Регистрируем таксономию Глубины
add_action('init', function () {
    $labels_tag = [
        'name' => 'Глубины',
        'singular_name' => 'Глубина',
        'menu_name' => 'Глубины',
        'search_items' => 'Поиск по глубине',
        'all_items' => 'Все глубины',
        'edit_item' => 'Редактировать глубину',
        'view_item' => 'Посмотреть страницу глубины',
        'update_item' => 'Сохранить глубину',
        'add_new_item' => 'Добавить новую глубину',
        'new_item_name' => 'Имя новую глубину',
        'not_found' => 'Глубин не найдено',
        'back_to_items' => 'Назад на страницу глубин',
    ];
    $args_tag = [
        'labels' => $labels_tag,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'show_in_quick_edit' => false,
        'meta_box_cb' => false,
    ];
    register_taxonomy('deep', ['geopoints'], $args_tag);
});

// Добавляем глубине характеристику Цвет маркера
add_action('deep_add_form_fields', function ($taxonomy) {
    ?>
    <div class="form-field">
        <label for="deep-color">Цвет маркера</label>
        <input type="color" name="deep-color" id="deep-color" />
    </div>
    <?php
});

add_action('deep_edit_form_fields', function ($term, $taxonomy) {
    // получаем значение поля
    $color = get_term_meta($term->term_id, 'deep-color', true);
    ?>
    <tr class="form-field">
        <th><label for="deep-color">Цвет маркера</label></th>
        <td><input name="deep-color" id="deep-color" type="color" value="<?= esc_attr($color); ?>" /></td>
    </tr>
    <?php
}, 10, 2);

add_action('created_deep', 'deep_save_term_fields');
add_action('edited_deep', 'deep_save_term_fields');

function deep_save_term_fields($term_id) {
    if (isset($_POST['deep-color'])) {
        update_term_meta($term_id, 'deep-color', sanitize_text_field($_POST['deep-color']));
    } else {
        delete_term_meta($term_id, 'deep-color');
    }
}

// создаем новую колонку
add_filter('manage_edit-deep_columns', function ($columns) {
    return array_slice($columns, 0, 2) + ['deep-color' => 'Цвет'] + array_slice($columns, $num);
}, 4);

// заполняем колонку данными
add_filter('manage_deep_custom_column', function ($content, $column_name, $term_id) {
    return $content .= "<div style='width:50px;height:20px;background-color:" . get_term_meta($term_id, 'deep-color', true) . "' />";
}, 10, 3);

// добавление поля в rest api
add_action('rest_api_init', function () {
    register_rest_field('deep', 'deep-color', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_term_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value)
                return;
            return update_term_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
});

// Регистрируем таксономию Даты
add_action('init', function () {

    $labels_tag = [
        'name' => 'Периоды работ',
        'singular_name' => 'Период работ',
        'menu_name' => 'Периоды работ',
        'search_items' => 'Поиск по периодам работ',
        'all_items' => 'Все периоды работ',
        'edit_item' => 'Редактировать период работ',
        'view_item' => 'Посмотреть страницу периода работ',
        'update_item' => 'Сохранить период работ',
        'add_new_item' => 'Добавить новый период работ',
        'new_item_name' => 'Наименование нового периода работ',
        'not_found' => 'Периодов работ не найдено',
        'back_to_items' => 'Назад на страницу периодов работ',
    ];
    $args_tag = [
        'labels' => $labels_tag,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'hierarchical' => true
    ];
    register_taxonomy('workdate', ['geopoints'], $args_tag);
});

// Добавляем датам характеристику npp, начало и конец
add_action('workdate_add_form_fields', function ($taxonomy) {
    ?>
    <div class="form-field">
        <label for="workdate-start">Начало периода</label>
        <input type="number" min="0" max="5000" name="workdate-start" id="workdate-start" />
    </div>
    <div class="form-field">
        <label for="workdate-stop">Конец периода</label>
        <input type="number" min="0" max="5000" name="workdate-stop" id="workdate-stop" />
    </div>
    <div class="form-field">
        <label for="workdate-npp">Номер по порядку</label>
        <input type="number" min="0" max="100" name="workdate-npp" id="workdate-npp" />
    </div>
    <?php
});

add_action('workdate_edit_form_fields', function ($term, $taxonomy) {
    // получаем значение поля
    $start = get_term_meta($term->term_id, 'workdate-start', true);
    $stop = get_term_meta($term->term_id, 'workdate-stop', true);
    $npp = get_term_meta($term->term_id, 'workdate-npp', true);
    ?>
    <tr class="form-field">
        <th><label for="workdate-start">Начало периода</label></th>
        <td><input name="workdate-start" id="workdate-start" type="number" min="0" max="5000"
                value="<?= esc_attr($start); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th><label for="workdate-start">Конец периода</label></th>
        <td><input name="workdate-stop" id="workdate-stop" type="number" min="0" max="5000"
                value="<?= esc_attr($stop); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th><label for="workdate-start">Номер по порядку</label></th>
        <td><input name="workdate-npp" id="workdate-npp" type="number" min="0" max="100"
                value="<?= esc_attr($npp); ?>" /></td>
    </tr>
    <?php
}, 10, 2);

add_action('created_workdate', 'workdate_save_term_fields');
add_action('edited_workdate', 'workdate_save_term_fields');

function workdate_save_term_fields($term_id){
    if (isset($_POST['workdate-start'])) {
        update_term_meta($term_id, 'workdate-start', sanitize_text_field($_POST['workdate-start']));
    } else {
        delete_term_meta($term_id, 'workdate-start');
    }
    if (isset($_POST['workdate-stop'])) {
        update_term_meta($term_id, 'workdate-stop', sanitize_text_field($_POST['workdate-stop']));
    } else {
        delete_term_meta($term_id, 'workdate-stop');
    }
    if (isset($_POST['workdate-npp'])) {
        update_term_meta($term_id, 'workdate-npp', sanitize_text_field($_POST['workdate-npp']));
    } else {
        delete_term_meta($term_id, 'workdate-npp');
    }
}

// создаем новую колонку
add_filter('manage_edit-workdate_columns', function ($columns) {
    return array_slice($columns, 0, 2) + ['workdate-period' => 'Период', 'workdate-npp' => 'Порядок'] + array_slice($columns, $num);
}, 4);

// заполняем колонку данными
add_filter('manage_workdate_custom_column', function ($content, $column_name, $term_id) {
    if ($column_name == 'workdate-period')
        return $content .= get_term_meta($term_id, 'workdate-start', true) . " - " . get_term_meta($term_id, 'workdate-stop', true);
    return $content .= get_term_meta($term_id, 'workdate-npp', true);
}, 10, 3);


// добавление поля в rest api
add_action('rest_api_init', function () {
    register_rest_field('workdate', 'workdate-start', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_term_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_term_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
    register_rest_field('workdate', 'workdate-stop', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_term_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_term_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
    register_rest_field('workdate', 'workdate-npp', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_term_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_term_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
});

// Регистрируем таксономию Тип работ
add_action('init', function () {
    $labels_tag = [
        'name' => 'Типы работ',
        'singular_name' => 'Тип работ',
        'menu_name' => 'Типы работ',
        'search_items' => 'Поиск по типам работ',
        'all_items' => 'Все типы работ',
        'edit_item' => 'Редактировать тип работ',
        'view_item' => 'Посмотреть страницу типа работ',
        'update_item' => 'Сохранить тип работы',
        'add_new_item' => 'Добавить новый тип работ',
        'new_item_name' => 'Название нового типа работ',
        'not_found' => 'Типов работ не найдено',
        'back_to_items' => 'Назад на страницу типов работ',
    ];
    $args_tag = [
        'labels' => $labels_tag,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'show_in_quick_edit' => false,
        'meta_box_cb' => false,
    ];
    register_taxonomy('worktype', ['geopoints'], $args_tag);
});

// Добавляем типу характеристику Цвет маркера
add_action('worktype_add_form_fields', function ($taxonomy) {
    ?>
    <div class="form-field">
        <label for="worktype-color">Цвет маркера</label>
        <input type="color" name="worktype-color" id="worktype-color" />
    </div>
    <?php
});

add_action('worktype_edit_form_fields', function ($term, $taxonomy) {
    // получаем значение поля
    $color = get_term_meta($term->term_id, 'worktype-color', true);
    ?>
    <tr class="form-field">
        <th><label for="worktype-color">Цвет маркера</label></th>
        <td><input name="worktype-color" id="worktype-color" type="color" value="<?= esc_attr($color); ?>" /></td>
    </tr>
    <?php
}, 10, 2);

add_action('created_worktype', 'worktype_save_term_fields');
add_action('edited_worktype', 'worktype_save_term_fields');

function worktype_save_term_fields($term_id){
    if (isset($_POST['worktype-color'])) {
        update_term_meta($term_id, 'worktype-color', sanitize_text_field($_POST['worktype-color']));
    } else {
        delete_term_meta($term_id, 'worktype-color');
    }
}

// создаем новую колонку
add_filter('manage_edit-worktype_columns', function ($columns) {
    return array_slice($columns, 0, 2) + ['worktype-color' => 'Цвет'] + array_slice($columns, $num);
}, 4);

// заполняем колонку данными
add_filter('manage_worktype_custom_column', function ($content, $column_name, $term_id) {
    return $content .= "<div style='width:50px;height:20px;background-color:" . get_term_meta($term_id, 'worktype-color', true) . "' />";
}, 10, 3);

// добавление поля в rest api
add_action('rest_api_init', function () {
    register_rest_field('worktype', 'worktype-color', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_term_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_term_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
});

// Добавляем панели редактирования своих полей в админку
add_action('admin_init', function () {
    add_meta_box(
        'geopoints_map_box',
        'Координаты работы',
        'display_geopoints_map_box',
        'geopoints',
        'normal',
        'high'
    );
    add_meta_box(
        'geopoints_meta_box',
        'Информация о работе',
        'display_geopoints_meta_box',
        'geopoints',
        'normal',
        'high'
    );
    add_meta_box(
        'geopoints_pages_box',
        'Связанные статьи',
        'display_geopoints_pages_box',
        'geopoints',
        'normal',
        'high'
    );
});

function display_geopoints_pages_box($geopoints) {
    wp_enqueue_script('my_pages_script', plugin_dir_url(__FILE__) . '/js/page_multiselect.js');
    $geopoints_pages = get_post_meta($geopoints->ID, 'geopoints_pages', true);
    // преобразовываем массив выбранных страниц в удобный для select вид
    $gps = [];foreach($geopoints_pages as $gp) $gps[$gp] = true;
    $all_pages = get_posts(['post_type'=>'post', 'posts_per_page'=>-1, 'orderby'=>'post_title', 'order'=>'ASC' ]);
    if( $all_pages ){?>
        <select name="geopoints_pages[]" style="width: 100%;" id="geopoint_pages-input" multiple  multiselect-search="true"><?php
        foreach( $all_pages as $p ){
            ?><option value="<?= $p->ID; ?>" <?= ($gps[$p->ID])?'selected':''; ?>>
                <?= esc_html($p->post_title); ?>
            </option><?php
        }?>
        </select><?php
    }
}

function display_geopoints_map_box($geopoints) {
	wp_enqueue_script('YandexMapAPI-alt-js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=ba0d0589-f903-43e9-b3d3-cc290988b69a&ver=2.1');
    wp_enqueue_script('my_yamap_script', plugin_dir_url(__FILE__) . '/js/adminMap.js');
    $geopoints_coord = esc_html(get_post_meta($geopoints->ID, 'geopoints_coord', true));
    ?>
    <div id="map" style="width: 100%; height:300px"></div>
    <input type="search" size="80" style="width: 100%;margin-top: 12px;text-align: center;" id="geopoint_coord-input" name="geopoints_coord"
        value="<?= esc_html($geopoints_coord); ?>" />
    <?php
}

function display_geopoints_meta_box($geopoints) {
    $deepId = get_the_terms($geopoints->ID, 'deep')[0]->term_id;
    $headmanId = get_the_terms($geopoints->ID, 'headman')[0]->term_id;
    $worktypeId = get_the_terms($geopoints->ID, 'worktype')[0]->term_id;

    $address = get_post_meta($geopoints->ID, 'address', true);
    $area = get_post_meta($geopoints->ID, 'area', true);
    $begin_time = get_post_meta($geopoints->ID, 'begin_time', true);
    $end_time = get_post_meta($geopoints->ID, 'end_time', true);
    $imgSrc = get_post_meta($geopoints->ID, 'imgSrc', true);
    ?>
    <table style="width: 100%">
        <tr>
            <td>Тип работ</td>
            <td>
                <select style="width: 100%" name="geopoints_worktype">
                    <option>—</option>
                    <?php

                    $worktypes = get_terms([
                        'taxonomy' => 'worktype',
                        'hide_empty' => false,
                    ]);

                    foreach ($worktypes as $worktype) {
                        ?>
                        <option value="<?php echo $worktype->term_id; ?>" <?php echo selected($worktype->term_id, $worktypeId); ?>>
                            <?php echo esc_html($worktype->name); ?>
                        </option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Руководитель работ</td>
            <td>
                <select style="width: 100%" name="geopoints_headman">
                    <option>—</option>
                    <?php

                    $headmans = get_terms([
                        'taxonomy' => 'headman',
                        'hide_empty' => false,
                    ]);

                    foreach ($headmans as $headman) {
                        ?>
                        <option value="<?php echo $headman->term_id; ?>" <?php echo selected($headman->term_id, $headmanId); ?>>
                            <?php echo esc_html($headman->name); ?>
                        </option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Глубина работ</td>
            <td>
                <select style="width: 100%" name="geopoints_deep">
                    <option>—</option>
                    <?php

                    $deeps = get_terms([
                        'taxonomy' => 'deep',
                        'hide_empty' => false,
                    ]);

                    foreach ($deeps as $deep) {
                        ?>
                        <option value="<?php echo $deep->term_id; ?>" <?php echo selected($deep->term_id, $deepId); ?>>
                            <?php echo esc_html($deep->name); ?>
                        </option>
                        <?php
                    } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td style="width: 150px">Адрес на карте</td>
            <td>
                <input type="text" style="width: 100%" name="address" value="<?= esc_html($address) ?>" />
            </td>
        </tr>
        <tr>
            <td style="width: 150px">Площадь работ</td>
            <td>
                <input type="number" min="0" style="width: 100%;text-align: center;padding-right:0;" name="area" value="<?= ($area)?esc_html($area):0 ?>" />
            </td>
        </tr>
        <tr>
        <tr>
            <td style="width: 150px">Адрес картинки превью</td>
            <td>
                <input type="text" style="width: 100%;padding-right:0;" name="imgsrc" value="<?= ($imgSrc)?esc_html($imgSrc):'' ?>" />
            </td>
        </tr>
        <tr>
            <td style="width: 150px">Даты начала и окончания работ</td>
            <td>
                <input type="number" min="0" 
                    style="width: 50%;margin: 0;padding-right: 0;border-top-right-radius: 0;border-bottom-right-radius: 0;border-right-width: 0;text-align: center;" 
                    name="begin_time" value="<?= ($begin_time) ? esc_html($begin_time) : 0 ?>" /><input type="number" min="0" 
                    style="width: 50%;margin: 0;padding-right: 0;border-top-left-radius: 0;border-bottom-left-radius: 0;text-align: center;" 
                    name="end_time" value="<?= ($end_time) ? esc_html($end_time) : 0 ?>" />
            </td>
        </tr>
    </table>
    <?php
}

// сохранение своих полей в админке
add_action('save_post', function ($geopoints_id, $geopoints) {
    global $wpdb;
    if ($geopoints->post_type == 'geopoints') {
        if (isset($_POST['geopoints_deep'])) {
            $deepId = (int) $_POST['geopoints_deep'];
            wp_set_object_terms($geopoints_id, $deepId, 'deep', $append = false);
        }
        if (isset($_POST['geopoints_headman'])) {
            $headmanId = (int) $_POST['geopoints_headman'];
            wp_set_object_terms($geopoints_id, $headmanId, 'headman', $append = false);
        }
        if (isset($_POST['geopoints_worktype'])) {
            $worktypeId = (int) $_POST['geopoints_worktype'];
            wp_set_object_terms($geopoints_id, $worktypeId, 'worktype', $append = false);
        }
        if (isset($_POST['geopoints_coord']) && $_POST['geopoints_coord'] != '') {
            update_post_meta($geopoints_id, 'geopoints_coord', $_POST['geopoints_coord']);
        } else {
            update_post_meta($geopoints_id, 'geopoints_coord', '');
        }
        if (isset($_POST['geopoints_pages'])) {
            update_post_meta($geopoints_id, 'geopoints_pages', $_POST['geopoints_pages']);
        } else {
            update_post_meta($geopoints_id, 'geopoints_pages', []);
        }
        if (isset($_POST['address']) && $_POST['address'] != '') {
            update_post_meta($geopoints_id, 'address', $_POST['address']);
        }
        if (isset($_POST['area']) && $_POST['area'] != '') {
            update_post_meta($geopoints_id, 'area', $_POST['area']);
        }
        if (isset($_POST['imgsrc'])) {
            update_post_meta($geopoints_id, 'imgSrc', $_POST['imgsrc']);
        }
        if (isset($_POST['begin_time']) && $_POST['begin_time'] != '' && 
            isset($_POST['end_time']) && $_POST['end_time'] != '') {

            update_post_meta($geopoints_id, 'end_time', $_POST['end_time']);
            update_post_meta($geopoints_id, 'begin_time', $_POST['begin_time']);

            $workdates = [];
            $starts = $wpdb->get_results( 'SELECT * FROM `wp_termmeta` where meta_key = "workdate-start";');
            $stops  = $wpdb->get_results( 'SELECT * FROM `wp_termmeta` where meta_key = "workdate-stop";');
            
            foreach($starts as $id => $start) {
                $bt =  (int) $_POST['begin_time'];
                $et =  (int) $_POST['end_time'];
                $bgn = (int) $start->meta_value;
                $end = (int) $stops[$id]->meta_value;

                if ($et == 0) $et = 9999;
                if (
                    $bt >= $bgn && $bt <= $end ||
                    $et >= $bgn && $et <= $end ||
                    $bt <= $bgn && $et >= $end ||
                    $bt >= $bgn && $et <= $end
                )
                    $workdates[] = (int) $start->term_id;
            }
            wp_set_object_terms($geopoints_id, $workdates, 'workdate', $append = false);
        }
    }
}, 10, 2);