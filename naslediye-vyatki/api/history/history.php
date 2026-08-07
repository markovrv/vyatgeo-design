<?php
/* 
Plugin Name: История города Хлынова/Вятки/Кирова: Люди и даты
*/

// создаем страницу плагина при активации
register_activation_hook( dirname(__FILE__).'/history.php', function() {
    $page = [
        'post_status' => 'publish' ,
        'post_title' => 'О разделе',
        'post_name' => 'history-about',
        'post_type' => 'page',
        'post_content' => ' <p>Киров – крупный город в России, административный центр Кировской области. Город образует самостоятельное муниципальное образование со статусом городского округа.</p>
                            <p>Город расположен на реке Вятке, в 896 км от Москвы в направлении на северо-восток и является одним из старейших городов России.</p>
                            <p>Годом его основания считается 1374 год. В течение почти двухсот лет город носил название Хлынов, затем в 1780 г. вновь был переименован в Вятку, а свое современное название получил в 1934 году.</p>
                            <p>Издревле город был известен как местный центр ремесел и торговли. Экономическую основу до революции на Вятской земле составляло производство продуктов сельского хозяйства и их переработка. Значительный вклад в экономический потенциал Вятки вносили мелкие предприятия кожевенного, свечного, шубно-овчинного, спичечного производства, производство бумаги.</p>
                            <p>В 2012 году городу Кирову присвоено почетное звание Кировской области «Город трудовой славы».</p>
                            <p>Ниже приведены основные даты истории города.</p>'
    ];
    if (!get_page_by_path( 'history-about' )) wp_insert_post( $page );

    $page = [
        'post_status' => 'publish' ,
        'post_title' => 'История города Хлынова/Вятки/Кирова: Люди и даты',
        'post_name' => 'history-spa',
        'post_type' => 'page',
        'post_content' => '<style>
input[type=date] {
    width: auto;
}

#app {
    margin-top: 16px;
}

.p-select {
    border-width: var(--theme-form-field-border-width, 1px);
    border-style: var(--theme-form-field-border-style, solid);
    border-color: var(--theme-form-field-border-initial-color);
    border-radius: var(--has-classic-forms, var(--theme-form-field-border-radius, 3px));
    background-color: var(--has-classic-forms, var(--theme-form-field-background-initial-color));
}

.p-scrolltop {
    display: none;
}

body, h1, .ct-breadcrumbs {
    color: var(--body-text-color);
}

.card {
    max-width: none;
    border: none;
    padding: 0;
}

article {
    background: var(--card-bg)!important;
}

</style>

<script>
    // Пример настройки параметров скрипта
    window.apiUrl = "../../"; // адрес апи для получения данных
    window.theme = "white"; // тема по умолчанию
    window.primary = 10; // цвет элементов. по умолчанию - синий
    window.surface = 3; // цвет акцента. по умолчанию - серый
</script>

<div id="app"></div>'
    ];
    if (!get_page_by_path( 'history-spa' )) wp_insert_post( $page );
});

// Объявляем шаблон для просмотра данных
add_filter('template_include', function ($template_path) {
    if (get_post_type() == 'history' && is_archive()) {
        // Активируем SPA
        wp_enqueue_script( 'vue1', plugin_dir_url(__FILE__) . 'vue/index.js',[],'1.1',['type' => 'module', 'strategy' => 'defer', 'crossorigin' => 'anonymous'] );
        wp_enqueue_style( 'vue2', plugin_dir_url(__FILE__) . 'vue/index.css?1.1' );
        // Активируем свой шаблон архива
        add_filter('blocksy:posts-listing:cards:custom-output', function ($card_render) {
            //return null; // эта строка отключает свой шаблон, отобразится шаблон из темы
            $card_render['has_default_layout'] = true;
            $card_render['output'] = '
            <div class="row-dates">
                <div class="col-date">' . get_post_meta(get_the_ID(), 'history_date_text', true) . '</div>
                <div class="col-date-line"><a href="' . get_permalink() . '" rel="bookmark"><img src="' . get_the_post_thumbnail_url(get_the_ID(), array (50, 50)) . '"></a></div>
                <div class="col-content"><a href="' . get_permalink() . '" rel="bookmark">' . get_the_title() . '</a></div>
            </div>
            ';
            return $card_render;
        }, 1);
    } elseif( is_page('history-spa') ){
        // Активируем SPA
        wp_enqueue_script( 'vue1', plugin_dir_url(__FILE__) . 'spa/index.js',[],'1.1',['type' => 'module', 'strategy' => 'defer', 'crossorigin' => 'anonymous'] );
        wp_enqueue_style( 'vue2', plugin_dir_url(__FILE__) . 'spa/index.css?1.1' );
    }
    return $template_path;
}, 1);


// Регистрируем объект История
add_action('init', function () {
    register_post_type('history', [
        'labels' => [
            'name' => 'Даты',
            'singular_name' => 'Дата',
            'add_new' => 'Добавить дату',
            'add_new_item' => 'Добавить новую дату',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить дату',
            'new_item' => 'Новая дата',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть дату',
            'search_items' => 'Поиск дат',
            'not_found' => 'Даты не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительская дата'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 15,
        'supports' => ['title', 'editor', 'comments', 'revisions', 'thumbnail', 'post-thumbnails'],
        'taxonomies' => ['city'],
        'menu_icon' => "dashicons-calendar",
        'has_archive' => true
    ]);
});

// Таксономия «Город»: каждая запись «Даты» относится ровно к одному городу
// (используем hierarchical-таксономию — привычный editors чекбокс-список, а
// не свободный tag-инпут). Ленты времени на фронте выводятся строго по
// городам (см. history/v1/events). В фильтрах каталога эта таксономия не
// участвует — выбор города происходит через отдельную страницу выбора
// (HistoricCitiesView.vue), а не через чипы фильтра.
add_action('init', function () {
    register_taxonomy('city', ['history'], [
        'labels' => [
            'name' => 'Города',
            'singular_name' => 'Город',
            'search_items' => 'Поиск городов',
            'all_items' => 'Все города',
            'parent_item' => 'Родительский город',
            'parent_item_colon' => 'Родительский город:',
            'edit_item' => 'Изменить город',
            'update_item' => 'Обновить город',
            'add_new_item' => 'Добавить город',
            'new_item_name' => 'Название нового города',
            'menu_name' => 'Города',
        ],
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud' => false,
    ]);

    register_term_meta('city', 'city_short', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
    register_term_meta('city', 'city_photo', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
    ]);
    // "lat, lng" строкой — тот же формат, что у attraction_coord (см.
    // api/attraction/rest-api.php), нужен для пина города на карте
    // (HistoricCitiesView.vue, режим "На карте").
    register_term_meta('city', 'city_coord', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);
});

// Доп. поля термина «Город» в админке: краткая характеристика (для карточки
// каталога) и фото (у терминов нет нативной миниатюры, поэтому свой
// media-picker на wp.media). Одна и та же разметка/скрипт на экранах
// добавления и редактирования — отличается только обёртка (div/tr), это
// стандартные хуки WP для таксономий.
add_action('city_add_form_fields', function () {
    wp_enqueue_media();
    history_city_map_admin_scripts();
    ?>
    <div class="form-field">
        <label for="city_short">Краткая характеристика</label>
        <textarea name="city_short" id="city_short" rows="3"></textarea>
        <p>Короткий текст для карточки города на странице выбора города.</p>
    </div>
    <div class="form-field">
        <label for="city_photo_id">Фото города</label>
        <input type="hidden" name="city_photo" id="city_photo_id" value="">
        <div id="city_photo_preview"></div>
        <button type="button" class="button" id="city_photo_button">Выбрать фото</button>
    </div>
    <div class="form-field">
        <label for="city_coord">Координаты (широта, долгота)</label>
        <div id="city-map" style="width:100%;max-width:500px;height:300px;"></div>
        <input type="search" style="width:100%;max-width:500px;margin-top:8px;" name="city_coord" id="city_coord" placeholder="58.603, 49.668">
        <p>Клик по карте или ввод вручную. Нужны для пина города на карте в разделе «Исторические города».</p>
    </div>
    <?php echo history_city_photo_picker_script(); ?>
    <?php
});

add_action('city_edit_form_fields', function ($term) {
    wp_enqueue_media();
    history_city_map_admin_scripts();
    $short = get_term_meta($term->term_id, 'city_short', true);
    $photo_id = get_term_meta($term->term_id, 'city_photo', true);
    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
    $coord = get_term_meta($term->term_id, 'city_coord', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="city_short">Краткая характеристика</label></th>
        <td><textarea name="city_short" id="city_short" rows="3" style="width:100%;max-width:500px;"><?= esc_textarea($short) ?></textarea></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="city_photo_id">Фото города</label></th>
        <td>
            <input type="hidden" name="city_photo" id="city_photo_id" value="<?= esc_attr($photo_id) ?>">
            <div id="city_photo_preview"><?php if ($photo_url): ?><img src="<?= esc_url($photo_url) ?>" style="max-width:150px;display:block;"><?php endif; ?></div>
            <button type="button" class="button" id="city_photo_button" style="margin-top:8px;">Выбрать фото</button>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="city_coord">Координаты (широта, долгота)</label></th>
        <td>
            <div id="city-map" style="width:100%;max-width:500px;height:300px;"></div>
            <input type="search" style="width:100%;max-width:500px;margin-top:8px;" name="city_coord" id="city_coord" placeholder="58.603, 49.668" value="<?= esc_attr($coord) ?>">
            <p class="description">Клик по карте или ввод вручную. Нужны для пина города на карте в разделе «Исторические города».</p>
        </td>
    </tr>
    <?php echo history_city_photo_picker_script(); ?>
    <?php
}, 10, 2);

// Тот же классический Yandex Maps JS API (v2.1), что уже используется в
// админке "Архитектуры"/"Гео-точек" (см. api/attraction/register-attraction.php,
// api/geopoints/register-geopoints.php) — общий handle/apikey, поэтому
// повторный enqueue безопасен, даже если он уже зарегистрирован где-то ещё.
function history_city_map_admin_scripts() {
    wp_enqueue_script('YandexMapAPI-alt-js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=ba0d0589-f903-43e9-b3d3-cc290988b69a&ver=2.1');
    wp_enqueue_script('history_city_map_script', plugin_dir_url(__FILE__) . 'js/adminCityMap.js');
}

function history_city_photo_picker_script() {
    ob_start();
    ?>
    <script>
    jQuery(function ($) {
        var frame;
        $('#city_photo_button').on('click', function (e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'Выберите фото города', multiple: false });
            frame.on('select', function () {
                var att = frame.state().get('selection').first().toJSON();
                $('#city_photo_id').val(att.id);
                $('#city_photo_preview').html('<img src="' + att.url + '" style="max-width:150px;display:block;">');
            });
            frame.open();
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_action('created_city', 'save_history_city_term_fields');
add_action('edited_city', 'save_history_city_term_fields');
function save_history_city_term_fields($term_id) {
    if (isset($_POST['city_short'])) {
        update_term_meta($term_id, 'city_short', sanitize_textarea_field($_POST['city_short']));
    }
    if (isset($_POST['city_photo'])) {
        update_term_meta($term_id, 'city_photo', intval($_POST['city_photo']));
    }
    if (isset($_POST['city_coord'])) {
        update_term_meta($term_id, 'city_coord', sanitize_text_field($_POST['city_coord']));
    }
}

// Координаты центра Кирова известны заранее (см. api/attraction/js/adminMap.js) —
// подставляем сразу, чтобы пин на карте городов появился без ручного шага в
// админке. Гвардом на пустое значение защищаем ручное редактирование в
// будущем: код перезапускается на каждый init, но перезаписывать уже
// заданное (в т.ч. вручную изменённое) значение не должен.
add_action('init', function () {
    $kirov = get_term_by('slug', 'kirov', 'city');
    if ($kirov && !get_term_meta($kirov->term_id, 'city_coord', true)) {
        update_term_meta($kirov->term_id, 'city_coord', '58.603, 49.668');
    }
}, 21);

// Единоразовый сидинг: 7 городов проекта (термины таксономии «Город») и
// привязка уже существующих записей «Даты» к Кирову — на момент внедрения
// таксономии данные были только по Кирову. Флаг в опциях защищает от
// повторного запуска на каждый init; повторно менять принадлежность записей
// городу этот код больше не будет (editors управляют этим вручную дальше).
add_action('init', function () {
    if (get_option('history_city_seeded')) {
        return;
    }

    $cities = [
        'kirov' => [
            'name' => 'Киров',
            'description' => 'Киров – крупный город в России, административный центр Кировской области. Город расположен на реке Вятке и является одним из старейших городов России. Годом его основания считается 1374 год. В течение почти двухсот лет город носил название Хлынов, затем в 1780 г. был переименован в Вятку, а свое современное название получил в 1934 году.',
            'short' => 'Главный город области: от древнего поселения новгородцев до крупного промышленного и культурного центра.',
        ],
        'kotelnich' => ['name' => 'Котельнич', 'description' => '', 'short' => 'Древний город на реке Вятке, известный уникальным палеонтологическим местонахождением.'],
        'orlov' => ['name' => 'Орлов', 'description' => '', 'short' => 'Небольшой купеческий город с застройкой провинциального классицизма.'],
        'slobodskoy' => ['name' => 'Слободской', 'description' => '', 'short' => 'Крупнейший торговый город Вятской земли с выдающимися памятниками архитектуры.'],
        'yaransk' => ['name' => 'Яранск', 'description' => '', 'short' => 'Культурный центр юго-запада области с архитектурными памятниками классицизма.'],
        'malmyzh' => ['name' => 'Малмыж', 'description' => '', 'short' => 'Торговый центр на пути из Вятки в Казань и Пермь.'],
        'urzhum' => ['name' => 'Уржум', 'description' => '', 'short' => 'Город, известный как место рождения советского государственного деятеля С.М. Кирова.'],
    ];

    foreach ($cities as $slug => $data) {
        if (!term_exists($slug, 'city')) {
            $inserted = wp_insert_term($data['name'], 'city', [
                'slug' => $slug,
                'description' => $data['description'],
            ]);
            if (!is_wp_error($inserted)) {
                update_term_meta($inserted['term_id'], 'city_short', $data['short']);
            }
        }
    }

    $history_ids = get_posts([
        'post_type' => 'history',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);
    foreach ($history_ids as $post_id) {
        wp_set_object_terms($post_id, 'kirov', 'city');
    }

    update_option('history_city_seeded', 1);
}, 20);

// Добавление мета-полей в стандартный апи
add_action('rest_api_init', function () {
    register_rest_field('history', 'history_date_value', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_post_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value)
                return;
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
    register_rest_field('history', 'history_date_text', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_post_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value)
                return;
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
});

// отключение редактора gutenberg в редакторе
add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'history')
        return false;
    return $current_status;
}, 10, 2);

// Добавляем панели редактирования своих полей в админку
add_action('admin_init', function () {
    add_meta_box(
        'history_meta_box',
        'Заголовок даты',
        'display_history_meta_box',
        'history',
        'normal',
        'high'
    );
});

function display_history_meta_box($history) {
    $history_date_value = esc_html(get_post_meta($history->ID, 'history_date_value', true));
    $history_date_text = esc_html(get_post_meta($history->ID, 'history_date_text', true));
    ?>
    <label for="history_date_value">Значение даты:</label>
    <input type="date" style="width: 100%;margin-bottom: 12px;" id="history_date_value" name="history_date_value"
        value="<?= esc_html($history_date_value); ?>" />
    <label for="history_date_text">Текст даты:</label>
    <input type="text" style="width: 100%;margin-bottom: 12px;" id="history_date_text" name="history_date_text"
        value="<?= esc_html($history_date_text); ?>" />
    <?php
}

// сохранение своих полей в админке
add_action('save_post', function ($history_id, $history) {
    global $wpdb;
    if ($history->post_type == 'history') {
        if (isset ($_POST['history_date_text']) && $_POST['history_date_text'] != '') {
            update_post_meta($history_id, 'history_date_text', $_POST['history_date_text']);
        } else {
            update_post_meta($history_id, 'history_date_text', '');
        }
        if (isset ($_POST['history_date_value']) && $_POST['history_date_value'] != '') {
            update_post_meta($history_id, 'history_date_value', $_POST['history_date_value']);
        } else {
            update_post_meta($history_id, 'history_date_value', '');
        }
    }
}, 10, 2);

function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// сортировка записей по дате
add_action('pre_get_posts', function ($query)
{
    if (is_archive() && is_post_type_archive('history') && $query->get('post_type') == "history" && !is_admin()):

        if ($_GET['sort_order'] && $_GET['sort_order'] == 'desc') $_COOKIE['sort_order'] = $_GET['sort_order'];
        if (isset($_GET['start_date']) && isValidDate($_GET['start_date'])) $_COOKIE['start_date'] = $_GET['start_date'];
        if (isset($_GET['end_date']) && isValidDate($_GET['end_date'])) $_COOKIE['end_date'] = $_GET['end_date'];

        if ($_COOKIE['sort_order'] && $_COOKIE['sort_order'] == 'desc') $query->set('order', 'DESC');
        else $query->set('order', 'ASC');
        $query->set('orderby', 'meta_value_num');
        $query->set('meta_key', 'history_date_value');

        // формируем фильтр
        $meta_query = [];
        if (isset($_COOKIE['start_date']) && isValidDate($_COOKIE['start_date'])) {
            $meta_query[] = [
                'key' => 'history_date_value',
                'value' => $_COOKIE['start_date'], // Указываем дату, с которой будем фильтровать
                'compare' => '>', // Сравнение больше
                'type' => 'DATE' // Указываем тип данных
            ];
        }
        if (isset($_COOKIE['end_date']) && isValidDate($_COOKIE['end_date'])) {
            $meta_query[] = [
                'key' => 'history_date_value',
                'value' => $_COOKIE['end_date'], // Указываем дату, с которой будем фильтровать
                'compare' => '<', // Сравнение меньше
                'type' => 'DATE' // Указываем тип данных
            ];
        }
        if (count($meta_query) > 1) {
            $meta_query[] = ['relation' => 'AND'];
        }
        if (count($meta_query) > 0) {
            $query->set('meta_query', $meta_query );
        }

    endif;
});



// REST-маршруты модуля «Города» — по конвенции attraction/v1, findings/v1
// (см. CLAUDE.md, «Паттерны модулей с реальным API»): bulk-список без
// пагинации на город + adjacent/nearby по датам в рамках того же города.
// Детальную запись событие фронт получает штатным /wp/v2/history/{id}?_embed=1
// (как useAttraction ходит в /wp/v2/attraction/{id}) — отдельный эндпоинт не
// нужен, history_date_text/history_date_value уже отданы через
// register_rest_field выше.
add_action('rest_api_init', function () {
    register_rest_route('history/v1', '/cities', [
        'methods' => 'GET',
        'callback' => 'get_history_cities',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('history/v1', '/events', [
        'methods' => 'GET',
        'callback' => 'get_history_events',
        'permission_callback' => '__return_true',
        'args' => [
            'city' => [
                'required' => true,
            ],
        ],
    ]);

    register_rest_route('history/v1', '/events/(?P<id>\d+)/adjacent', [
        'methods' => 'GET',
        'callback' => 'get_history_adjacent',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('history/v1', '/events/(?P<id>\d+)/nearby', [
        'methods' => 'GET',
        'callback' => 'get_history_nearby',
        'permission_callback' => '__return_true',
    ]);
});

// Фото городов (их всего 7 — резолвим сразу на сервере, без батч-апгрейда
// на фронте, как у карточек событий). Тот же URL используется и на карточке
// в каталоге городов, и на полноэкранном hero ленты времени — hero там
// крупнее (60vh на всю ширину), поэтому предпочитаем самый крупный
// доступный вариант, а не "среднюю" ступень: bulk-список событий отдаёт
// сырую миниатюру + id вложения, апгрейд качества делает фронт батч-запросом
// к /wp/v2/media (см. useHistoryThumbnails) — здесь же экономить не на чем,
// это одиночный запрос на редко меняющийся список из 7 записей.
function history_resolve_image_url($attachment_id) {
    if (!$attachment_id) {
        return '';
    }
    foreach (['full', 'large', 'medium_large', 'medium'] as $size) {
        $url = wp_get_attachment_image_url($attachment_id, $size);
        if ($url) {
            return $url;
        }
    }
    return wp_get_attachment_url($attachment_id) ?: '';
}

// "lat, lng" из city_coord → [lat, lng] или null, если поле не заполнено
// или заполнено некорректно (тот же формат, что у attraction_coord).
function history_parse_city_coord($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    $parts = array_map('floatval', explode(',', $raw));
    return count($parts) === 2 ? $parts : null;
}

function get_history_cities() {
    $terms = get_terms(['taxonomy' => 'city', 'hide_empty' => false]);
    $result = [];

    foreach ($terms as $term) {
        $photo_id = get_term_meta($term->term_id, 'city_photo', true);
        $result[] = [
            'id' => $term->term_id,
            'slug' => $term->slug,
            'name' => $term->name,
            'description' => $term->description,
            'short' => get_term_meta($term->term_id, 'city_short', true),
            'photo' => history_resolve_image_url($photo_id),
            // Отдельно от "photo" — та же миниатюра 150×150, что и у карточек
            // событий, специально для пина на карте (крупное фото там не нужно
            // и не помещается в круглый пин, см. HistoricCitiesView.vue).
            'photoThumb' => $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '',
            'coordinates' => history_parse_city_coord(get_term_meta($term->term_id, 'city_coord', true)),
            'eventsCount' => (int) $term->count,
        ];
    }

    return rest_ensure_response($result);
}

// Границы века для серверного meta_query по history_date_value (Y-m-d) — те
// же 5 корзин, что и кнопки быстрого выбора периода в старом плагине
// (history.php, "замена заголовка архива", setperiod(13,16) и т.п.).
function history_century_range($century) {
    $ranges = [
        '14-17' => ['1300-01-01', '1699-12-31'],
        '18' => ['1700-01-01', '1799-12-31'],
        '19' => ['1800-01-01', '1899-12-31'],
        '20' => ['1900-01-01', '1999-12-31'],
        '21' => ['2000-01-01', '2099-12-31'],
    ];
    return $ranges[$century] ?? null;
}

// Постранично, по 10 записей — у Кирова уже 666 событий, единым запросом
// (как у attraction/v1/objects) грузить смысла нет: карте тут делать нечего,
// лента времени рендерится строго последовательно, поэтому подгружаем по
// мере прокрутки. Фильтр по веку — тоже на сервере (meta_query по
// history_date_value), а не на клиенте поверх уже загруженного куска,
// иначе первая страница при активном фильтре могла бы прийти пустой,
// хотя подходящие записи есть дальше.
function get_history_events($request) {
    $city_slug = $request->get_param('city');
    $term = get_term_by('slug', $city_slug, 'city');

    if (!$term) {
        return new WP_Error('city_not_found', 'Город не найден', ['status' => 404]);
    }

    $page = max(1, intval($request->get_param('page') ?: 1));
    $per_page = max(1, intval($request->get_param('per_page') ?: 10));

    $meta_query = [];
    $range = history_century_range($request->get_param('century'));
    if ($range) {
        $meta_query[] = [
            'key' => 'history_date_value',
            'value' => $range,
            'compare' => 'BETWEEN',
            'type' => 'DATE',
        ];
    }

    $args = [
        'post_type' => 'history',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        // Вторичная сортировка по ID — детерминированный тай-брейк при
        // совпадающих history_date_value (в летописи Кирова таких десятки:
        // несколько событий одного года), иначе порядок записей с одинаковой
        // датой не гарантирован между запросами разных страниц.
        'orderby' => ['meta_value' => 'ASC', 'ID' => 'ASC'],
        'meta_key' => 'history_date_value',
        'meta_query' => $meta_query,
        'tax_query' => [[
            'taxonomy' => 'city',
            'field' => 'slug',
            'terms' => $city_slug,
        ]],
    ];

    // Поиск по названию/тексту события — тот же паттерн, что и в findings/v1
    // (artifact-finder/rest-api.php): штатный параметр 's' у WP_Query,
    // остальные условия (город, век, сортировка, пагинация) применяются как обычно.
    $search = $request->get_param('search');
    if (!empty($search)) {
        $args['s'] = sanitize_text_field($search);
    }

    $query = new WP_Query($args);

    $events = [];
    foreach ($query->posts as $post) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $events[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'dateText' => get_post_meta($post->ID, 'history_date_text', true),
            'dateValue' => get_post_meta($post->ID, 'history_date_value', true),
            'imgSrc' => $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'thumbnail') : '',
            'img' => $thumbnail_id,
        ];
    }

    return rest_ensure_response([
        'events' => $events,
        'pagination' => [
            'page' => $page,
            'perPage' => $per_page,
            'total' => (int) $query->found_posts,
            'totalPages' => (int) $query->max_num_pages,
        ],
    ]);
}

// Упорядоченные по дате id событий в рамках того же города, что и у $post_id
// — общий helper для /adjacent и /nearby, аналог format_attraction_nav_item.
function get_history_city_ordered_ids($post_id) {
    $terms = wp_get_post_terms($post_id, 'city', ['fields' => 'slugs']);
    $city_slug = $terms[0] ?? null;
    if (!$city_slug) {
        return [];
    }

    return get_posts([
        'post_type' => 'history',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        // Тот же тай-брейк по ID, что и в get_history_events — иначе соседство
        // "Предыдущий/Следующий" при совпадающих датах могло бы плавать между запросами.
        'orderby' => ['meta_value' => 'ASC', 'ID' => 'ASC'],
        'meta_key' => 'history_date_value',
        'fields' => 'ids',
        'tax_query' => [[
            'taxonomy' => 'city',
            'field' => 'slug',
            'terms' => $city_slug,
        ]],
    ]);
}

function format_history_nav_item($post_id) {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'dateText' => get_post_meta($post_id, 'history_date_text', true),
        'imgSrc' => $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'thumbnail') : '',
        'img' => $thumbnail_id,
    ];
}

// Предыдущее/следующее событие по дате в рамках того же города.
function get_history_adjacent($request) {
    $id = intval($request['id']);
    $ids = get_history_city_ordered_ids($id);
    $position = array_search($id, $ids);
    $result = ['prev' => null, 'next' => null];

    if ($position !== false) {
        if ($position > 0) {
            $result['prev'] = format_history_nav_item($ids[$position - 1]);
        }
        if ($position < count($ids) - 1) {
            $result['next'] = format_history_nav_item($ids[$position + 1]);
        }
    }

    return rest_ensure_response($result);
}

// Окно из до 5 событий (2 до, текущее, 2 после) по дате в рамках того же
// города — для мини-ленты навигации на странице события.
function get_history_nearby($request) {
    $id = intval($request['id']);
    $ids = get_history_city_ordered_ids($id);
    $position = array_search($id, $ids);

    if ($position === false) {
        return rest_ensure_response(['nearby' => []]);
    }

    $window_ids = array_slice($ids, max(0, $position - 2), 5);
    $nearby = array_map(function ($nid) use ($id) {
        $item = format_history_nav_item($nid);
        $item['active'] = ($nid === $id);
        return $item;
    }, $window_ids);

    return rest_ensure_response(['nearby' => $nearby]);
}

// свой апи с фильтром по дате
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/history/', array(
        'methods' => 'GET',
        'callback' => 'get_posts_by_meta',
        'permission_callback' => '__return_true',
    ));
});

function get_posts_by_meta($request) {
    $start = $request->get_param('start');
    $stop = $request->get_param('stop');
	$order = $request->get_param('order') ?: 'ASC'; // Порядок сортировки (по умолчанию ASC)
	
	// Параметры пагинации
    $paged = $request->get_param('page') ?: 1; // Номер страницы (по умолчанию 1)
    $per_page = $request->get_param('per_page') ?: 10; // Количество постов на странице (по умолчанию 10)

	$meta_query = [];
	if (isset($start) && isValidDate($start)) {
		$meta_query[] = [
			'key' => 'history_date_value',
			'value' => $start, // Указываем дату, с которой будем фильтровать
			'compare' => '>=', // Сравнение больше
			'type' => 'DATE' // Указываем тип данных
		];
	}
	if (isset($stop) && isValidDate($stop)) {
		$meta_query[] = [
			'key' => 'history_date_value',
			'value' => $stop, // Указываем дату, с которой будем фильтровать
			'compare' => '<', // Сравнение меньше
			'type' => 'DATE' // Указываем тип данных
		];
	}
	if (count($meta_query) > 1) {
		$meta_query[] = ['relation' => 'AND'];
	}
	
    $args = array(
        'post_type' => 'history',
        'meta_query' => $meta_query,
		'meta_key' => 'history_date_value', // Указываем метаполе для сортировки
        'orderby' => 'meta_value', // Сортируем по значению метаполя
        'order' => $order, // Порядок сортировки
		'paged' => $paged, // Параметр пагинации
        'posts_per_page' => $per_page // Количество постов на странице
    );

    $query = new WP_Query($args);
    $posts = $query->posts;
	
	// Формируем массив для ответа, включая метаполя
    $response = [];
    foreach ($posts as $post) {
        // Получаем метаполя
        $meta = get_post_meta($post->ID);
        $response[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'history_date_text' => $meta['history_date_text'][0],
			'history_date_value' => $meta['history_date_value'][0],
        ];
    }

    // Добавляем информацию о пагинации в ответ
    $total_posts = $query->found_posts;
    $total_pages = ceil($total_posts / $per_page);

    return rest_ensure_response([
        'posts' => $response,
        'pagination' => [
            'total_posts' => $total_posts,
            'total_pages' => $total_pages,
            'current_page' => (int) $paged,
            'posts_per_page' => (int) $per_page,
        ]
    ]);
}



// замена заголовка архива
add_action('blocksy:hero:after', function () {
    if (is_archive() && is_post_type_archive('history') && !is_admin()):
        ?>
            <div class="hero-section" data-type="type-1">
            <article class="entry-card post-6033 history type-history status-publish has-post-thumbnail hentry" style="border-radius: 10px; padding: 16px;">
                <div id="app"></div>
                <header class="entry-header">
                    <h1 class="page-title" title="История Хлынова/Кирова/Вятки" itemprop="headline" style="color: black; text-align: center;">
                        <span class="ct-title-label">История города Хлынова/Вятки/Кирова:</span> Люди и даты
                    </h1>
                    <p>Выберите интересующий вас период истории или введите свой и нажмите кнопку <b>Применить фильтр</b>.</p>
                    <style>



                        .col-date {
                            display: inline-block;
                            width: calc(40% - 70px);
                            vertical-align: top;
                            font-weight:bold;
                            text-align: center;
                            padding: 16px 0;
                            font-size: 160%;
                        }

                        .col-date-line {
                            display: inline-block;
                            width: 100px;
                            height:100px;
                            padding:10px;
                            vertical-align: top;
                        }

                        .col-date-line img {
                            border-radius: 50px;
                            width:100px;
                            
                        }

                        .col-content {
                            display: inline-block;
                            width: 50%;
                            vertical-align: top;
                            padding: 16px 0;
                            font-size: 120%;
                        }

                        .row-dates {
                            width: 100%;
                        }

                        .ct-pagination {
                            margin-bottom: 16px;
                        }


                        @media (max-width: 500px) {
                            
                            .col-date {
                                display: block;
                                width: 100%;
                                vertical-align: top;
                                font-weight:bold;
                                text-align: center;
                                padding: 0;
                            }

                            .col-date-line {
                                display: block;
                                width: 100%;
                                text-align: center;
                                height:100px;
                                padding:10px;
                                vertical-align: top;
                            }

                            .col-date-line img {
                                border-radius: 50px;
                                width:100px;

                            }

                            .col-content {
                                display: inline-block;
                                width: calc(100%);
                                vertical-align: top;
                                padding: 10px;
                            }
                            
                        }


                        .form-container {
                            display: flex;
                            flex-wrap: wrap;
                            justify-content: space-between;
                            margin-bottom: 20px;
                        }
                        .form-group {
                            flex: 1;
                            min-width: 200px; /* Минимальная ширина для каждого столбца */
                            margin: 10px;
                        }
                        label {
                            display: block;
                            margin-bottom: 5px;
                        }
                        input[type="date"], select {
                            width: 100%;
                            padding: 8px;
                            box-sizing: border-box;
                        }
                        @media (max-width: 600px) {
                            .form-container {
                                flex-direction: column; /* На маленьких экранах отображение в один столбец */
                            }
                        }
                    </style>
                    <script>
                        function setperiod(a, b) {
                            document.getElementById("sort_order").value = "asc";
                            document.getElementById("start_date").value = a + `00-01-01`;
                            document.getElementById("end_date").value = b + `99-12-31`;
                            saveForm();
                            document.getElementById("date_filter").submit();
                        }
                        function clearForm() {
                            document.getElementById("sort_order").value = "asc";
                            document.getElementById("start_date").value = "";
                            document.getElementById("end_date").value = "";
                            saveForm();
                            document.getElementById("date_filter").submit();
                        }
                        function saveForm() {
                            setCookie("sort_order", document.getElementById("sort_order").value, 1);
                            setCookie("start_date", document.getElementById("start_date").value, 1);
                            setCookie("end_date", document.getElementById("end_date").value, 1);
                        }
                        function setCookie(name, value, days) {
                            let expires = "";
                            if (days) {
                                const date = new Date();
                                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                expires = "; expires=" + date.toUTCString();
                            }
                            document.cookie = name + "=" + (value || "") + expires + "; path=/; secure; SameSite=Strict"; // Устанавливаем cookie с атрибутами secure и SameSite
                        }
                        function deleteCookie(name) {
                            document.cookie = name + '=; Max-Age=-99999999; path=/; secure; SameSite=Strict'; // Указываем те же атрибуты, что и при установке
                        }
                    </script>
                    <center>
                        <button class="button" style="margin-bottom: 10px;" onclick="setperiod(13, 16)">14-17 века</button>
                        <button class="button" style="margin-bottom: 10px;" onclick="setperiod(17, 17)">18 век</button>
                        <button class="button" style="margin-bottom: 10px;" onclick="setperiod(18, 18)">19 век</button>
                        <button class="button" style="margin-bottom: 10px;" onclick="setperiod(19, 19)">20 век</button>
                        <button class="button" style="margin-bottom: 10px;" onclick="setperiod(20, 20)">21 век</button>
                    </center>
                    <hr style="background-color: blue">
                    <form action="/history/" id="date_filter" method="GET" onsubmit="saveForm()">
                        <div class="form-container">
                            <div class="form-group">
                                <label for="sort_order">Порядок:</label>
                                <select name="sort_order" id="sort_order">
                                    <option value="asc" <?php echo ($_COOKIE['sort_order'] && $_COOKIE['sort_order'] == 'asc')?'selected':'' ?>>По возрастанию</option>
                                    <option value="desc" <?php echo ($_COOKIE['sort_order'] && $_COOKIE['sort_order'] == 'desc')?'selected':'' ?>>По убыванию</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="start_date">Дата начала:</label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo (isset($_COOKIE['start_date']) && isValidDate($_COOKIE['start_date']))?$_COOKIE['start_date']:''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="end_date">Дата окончания:</label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo (isset($_COOKIE['end_date']) && isValidDate($_COOKIE['end_date']))?$_COOKIE['end_date']:''; ?>">
                            </div>
                        </div>
                        <input type="submit" value="Применить фильтр">
                        <input type="button" class="button" onclick="clearForm()" value="Сбросить">
                    </form>
                </header>
            </article>
            </div>
        <?php
    endif;
    
    if (is_singular('history') && !is_admin()):
        ?>
            <div class="hero-section is-width-constrained" data-type="type-1" style="margin:0;">
                <header class="entry-header">
                    <h1 class="page-title" title="История Хлынова/Кирова/Вятки" itemprop="headline" style="text-align: center;">
                        <span class="ct-title-label" style="font-size: 150%;"><?php 
                            echo get_post_meta( get_the_ID(), 'history_date_text', true );
                            ?></span>
                    </h1>
                </header>
            </div>
<style>a img {max-width:100%! important;}</style>
        <?php
    endif;
});