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
        'taxonomies' => [],
        'menu_icon' => "dashicons-calendar",
        'has_archive' => true
    ]);
});

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