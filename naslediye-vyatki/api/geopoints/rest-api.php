<?php
if (!class_exists('WP_REST_Request')) {
      require_once(ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php');
}

// Экспорт точек для фронтенда (все точки сразу) V1
add_action('rest_api_init', function() {
    register_rest_route('geopoints/v1', '/points', [
        'methods' => 'GET',
        'callback' => 'get_geopoints_data',
        'permission_callback' => '__return_true'
    ]);
});

function get_geopoints_data() {
    global $wpdb;

    // загружаем все статьи в виде id => заголовок для связывания с точками
    $articles = $wpdb->get_results(
        "SELECT ID, post_title 
        FROM {$wpdb->posts} 
        WHERE post_type = 'post' AND post_status = 'publish'
        ORDER BY post_title ASC",
        OBJECT_K
    );

    // Оптимизированный запрос для точек
    $points = get_posts([
        'post_type' => 'geopoints',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'no_found_rows' => true,
        'update_post_term_cache' => true,
        'update_post_meta_cache' => true,
        'fields' => 'all',
    ]);

    $out = [];
    
    // Предзагружаем все метаданные для точек одним запросом
    $point_ids = wp_list_pluck($points, 'ID');
    update_meta_cache('post', $point_ids);

    foreach ($points as $post) {
        $post_id = $post->ID;
        
        // Получаем термины таксономий (оптимизировано)
        $headman_term = current(wp_get_post_terms($post_id, 'headman', ['fields' => 'all', 'number' => 1]));
        $worktype_slug = current(wp_get_post_terms($post_id, 'worktype', ['fields' => 'slugs', 'number' => 1]));
        $deep_id = current(wp_get_post_terms($post_id, 'deep', ['fields' => 'ids', 'number' => 1]));
        
        // Получаем мета-поля
        $coordinates = array_map('floatval', explode(', ', get_post_meta($post_id, 'geopoints_coord', true)));
        $geopoints_pages = maybe_unserialize(get_post_meta($post_id, 'geopoints_pages', true));
        $begin_time = (int) get_post_meta($post_id, 'begin_time', true);
        $end_time = (int) get_post_meta($post_id, 'end_time', true);
        $address = get_post_meta($post_id, 'address', true);
        $area = get_post_meta($post_id, 'area', true);

        // Формируем информацию о связанных статьях
        $info = '';
        if (!empty($geopoints_pages)) {
            $related_articles = [];
            foreach ((array)$geopoints_pages as $article_id) {
                if (isset($articles[$article_id])) {
                    $related_articles[] = sprintf(
                        '<br><a href="#/page/%d">%s</a>',
                        $article_id,
                        esc_html($articles[$article_id]->post_title)
                    );
                }
            }
            
            if ($related_articles) {
                $info = '<br><b>Связанные статьи:</b>' . implode('', $related_articles);
            }
        }

        $out[] = [
            "type" => "Feature", 
            "id" => $post_id, 
            "geometry" => [
                "type" => "Point", 
                "coordinates" => $coordinates, 
                "poly" => null, 
                "line" => null 
            ], 
            "properties" => [
                "balloonContentHeader" => $post->post_title, 
                "clusterCaption" => $post->post_title, 
                "hintContent" => $post->post_title, 
                "balloonContentBody" => $address . $info . sprintf("<br><a href='?p=%d'>подробнее...</a>", $post_id), 
                "balloonContentFooter" => $headman_term->name ?? '', 
                "manager" => $headman_term->term_id ?? '', 
                "depth" => $deep_id ?: '', 
                "type" => $worktype_slug ?: '', 
                "date" => $begin_time, 
                "date2" => $end_time, 
                "area" => $area ?: '' 
            ], 
            "options" => [
                "preset" => "islands#circleDotIcon", 
                "iconColor" => "#000000" 
            ] 
        ];
    }
    
    return $out;
}


// Экспорт точек V2
add_action('rest_api_init', function() {
    register_rest_route('geopoints/v2', '/points', [
        'methods' => 'GET',
        'callback' => 'get_geopoints_data_V2',
        'permission_callback' => '__return_true'
    ]);
});

function get_geopoints_data_V2() {
    global $wpdb;

    // загружаем все статьи в виде id => заголовок для связывания с точками
    $articles = $wpdb->get_results(
        "SELECT ID, post_title 
        FROM {$wpdb->posts} 
        WHERE post_type = 'post' AND post_status = 'publish'
        ORDER BY post_title ASC",
        OBJECT_K
    );

    // Оптимизированный запрос для точек
    $points = get_posts([
        'post_type' => 'geopoints',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'no_found_rows' => true,
        'update_post_term_cache' => true,
        'update_post_meta_cache' => true,
        'fields' => 'all',
    ]);

    $out = [];
    
    // Предзагружаем все метаданные для точек одним запросом
    $point_ids = wp_list_pluck($points, 'ID');
    update_meta_cache('post', $point_ids);

    foreach ($points as $post) {
        $post_id = $post->ID;
        
        // Получаем термины таксономий (оптимизировано)
        $headman_term = current(wp_get_post_terms($post_id, 'headman', ['fields' => 'all', 'number' => 1]));
        $worktype_slug = current(wp_get_post_terms($post_id, 'worktype', ['fields' => 'slugs', 'number' => 1]));
        $deep_id = current(wp_get_post_terms($post_id, 'deep', ['fields' => 'ids', 'number' => 1]));
        
        // Получаем мета-поля
        $coordinates = array_map('floatval', explode(', ', get_post_meta($post_id, 'geopoints_coord', true)));
        $geopoints_pages = maybe_unserialize(get_post_meta($post_id, 'geopoints_pages', true));
        $begin_time = (int) get_post_meta($post_id, 'begin_time', true);
        $end_time = (int) get_post_meta($post_id, 'end_time', true);
        $address = get_post_meta($post_id, 'address', true);
        $area = get_post_meta($post_id, 'area', true);
        $imgSrc = get_post_meta($post_id, 'imgSrc', true);
        $thumbnail_id = get_post_thumbnail_id($post_id);

        if (empty($imgSrc) && $thumbnail_id) {
            // Если imgSrc пустой, получаем URL thumbnail
            $imgSrc = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
            // Сохраняем URL миниатюры в метаполе imgSrc
            update_post_meta($post_id, 'imgSrc', $imgSrc);
        }
            
        // Формируем информацию о связанных статьях
        $info = [];
        if (!empty($geopoints_pages)) {
            foreach ((array)$geopoints_pages as $article_id) {
                if (isset($articles[$article_id])) {
                    $info[] = [
                        'id' => $article_id,
                        'title' => esc_html($articles[$article_id]->post_title)
                    ];
                }
            }
        }

        $out[] = [
            "id" => $post_id, 
            "coordinates" => $coordinates, 
            "title" => $post->post_title,  
            "place" => $address ?: '', 
            "imgSrc" => $imgSrc ?: '', 
            "img" => $thumbnail_id ?: '',
            "manager" => $headman_term->term_id ?: '', 
            "depth" => $deep_id ?: '', 
            "type" => $worktype_slug ?: '', 
            "date" => $begin_time, 
            "date2" => $end_time, 
            "area" => $area ?: ''  
        ];
    }
    
    return $out;
}


// экспорт временных периодов V1
add_action( 'rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/workdate', [
    'methods' => 'GET',
    'callback' => function() {
        // запрос у стандартного АПИ
        $request = new WP_REST_Request( 'GET', '/wp/v2/workdate' );
        $out = [];
        $request->set_param( 'per_page', 100 );
        $response = rest_do_request( $request );
        foreach($response->data as $p) {
            $out[] = [
                'id'   =>$p['id'],
                'text' =>$p['name'],
                'start'=>(int) $p['workdate-start'],
                'stop' =>(int) $p['workdate-stop'],
                'npp'  =>(int) $p['workdate-npp'],
            ];
        }
        return $out;
    },
    'permission_callback' => '__return_true'
  ]);
});

// Экспорт временных периодов V2
add_action('rest_api_init', function() {
    register_rest_route('geopoints/v2', '/workdate', [
        'methods' => 'GET',
        'callback' => function() {
            $out = [];
            $terms = get_terms([
                'taxonomy' => 'workdate',
                'hide_empty' => false,
                'number' => 100, // Ограничение количества элементов
                'orderby' => 'meta_value_num',
                'meta_key' => 'workdate-npp', // Сортировка по полю NPP
            ]);
            
            if (is_wp_error($terms)) {
                return [];
            }
            
            foreach ($terms as $term) {
                $out[] = [
                    'id' => $term->term_id,
                    'text' => $term->name,
                    'start' => (int) get_term_meta($term->term_id, 'workdate-start', true),
                    'stop' => (int) get_term_meta($term->term_id, 'workdate-stop', true),
                    'npp' => (int) get_term_meta($term->term_id, 'workdate-npp', true),
                ];
            }
            
            return $out;
        },
        'permission_callback' => '__return_true'
    ]);
});


// Получение всех постов V1
add_action( 'rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/posts', [
    'methods' => 'GET',
    'callback' => function() {
        // запрос у стандартного АПИ
        $request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
        $out = [];
        $request->set_param( 'per_page', 100 );
        $response = rest_do_request( $request );
        foreach($response->data as $p) {
            $out[] = [
                'name'      => $p['title']['rendered'],
                'id'        => $p['id'],
                'content'   => '',
                'category'  => $p['categories'][0],
                'type'      => 'page'  
            ];
        }
        return $out;
    },
        'permission_callback' => function (): bool {
            return true;
        }]);
});

// Получение всех постов V2
add_action('rest_api_init', function() {
    register_rest_route('geopoints/v2', '/posts', [
        'methods' => 'GET',
        'callback' => function() {
            $out = [];
            $posts = get_posts([
                'post_type' => 'post',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);

            foreach ($posts as $post) {
                $categories = wp_get_post_categories($post->ID, ['fields' => 'ids']);
                
                $out[] = [
                    'name' => $post->post_title,
                    'id' => $post->ID,
                    'category' => !empty($categories) ? $categories[0] : 0
                ];
            }

            return $out;
        },
        'permission_callback' => '__return_true'
    ]);
});


// Получение поста по номеру V1
add_action( 'rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/posts/(?P<id>\d+)', [
    'methods' => 'GET',
    'callback' => function($data) {
        $my_posts = get_posts( ['include' => [$data['id']]]);
        $categories = wp_get_post_terms( $data['id'], 'category');
        foreach($my_posts as $p) {
            $out = 
            [
                'name'      => $p->post_title,
                'id'        => $p->ID,
                'content'   => $p->post_content,
                'type'      => 'page',
                'category'  => $categories[0]->term_id
            ];
        }
        return $out;
    },
        'permission_callback' => function (): bool {
            return true;
        }]);
});

// Получение поста по номеру V2
add_action('rest_api_init', function() {
    register_rest_route('geopoints/v2', '/posts/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function($data) {
            $post_id = (int)$data['id'];
            
            // Получаем пост напрямую по ID
            $post = get_post($post_id);
            
            if (!$post || is_wp_error($post)) {
                return new WP_Error('not_found', 'Post not found', ['status' => 404]);
            }
            
            // Получаем категории (только первый элемент)
            $categories = get_the_terms($post_id, 'category');
            $category_id = !empty($categories) ? $categories[0]->term_id : 0;
            
            return [
                'name'      => $post->post_title,
                'id'        => $post->ID,
                'content'   => $post->post_content,
                'category'  => $category_id
            ];
        },
        'permission_callback' => '__return_true'
    ]);
});

// Получение постов и подкатегорий, связанных с указанной категорией V1
add_action( 'rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/cats/(?P<id>\d+)', [
    'methods' => 'GET',
    'callback' => function($data) {
        $my_cats = get_categories( ['include' => [$data['id']]]);
        $child_categories = get_categories(['child_of' => $data['id']]);
        $child_cat_ids = [];
        foreach($child_categories as $cat) {
            $child_cat_ids[] = $cat->ID;
        }
        $child_posts = get_posts(['category' => $data['id'], 'posts_per_page' => -1]);
        $child_post_ids = [];
        foreach($child_posts as $post) {
            $child_post_ids[] = $post->ID;
        }
        foreach($my_cats as $c) {
            $out = 
            [
                'name'      => $c->name,
                'id'        => $c->term_id,
                'content'   => [
                    'description' => '',
                    'pages'       => $child_post_ids,
                    'subcats'     => $child_cat_ids
                ],
                'parent'    => $c->parent,
                'type'      => 'cat'
            ];
        }
        return $out;
    },
        'permission_callback' => function (): bool {
            return true;
        }]);
});

// Получение постов и подкатегорий, связанных с указанной категорией V2
add_action('rest_api_init', function() {
    register_rest_route('geopoints/v2', '/cats/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function($data) {
            $category_id = (int)$data['id'];
            
            // Получаем основную категорию
            $main_category = get_term($category_id, 'category');
            if (is_wp_error($main_category) || !$main_category) {
                return new WP_Error('not_found', 'Category not found', ['status' => 404]);
            }
            
            // Получаем дочерние категории
            $child_categories = get_categories([
                'taxonomy' => 'category',
                'child_of' => $category_id,
                'hide_empty' => false,
                'fields' => 'ids' // Получаем только ID для оптимизации
            ]);
            
            // Получаем посты категории
            $child_posts = get_posts([
                'category' => $category_id,
                'posts_per_page' => -1,
                'fields' => 'ids' // Получаем только ID для оптимизации
            ]);
            
            // Формируем ответ
            return [
                'name' => $main_category->name,
                'id' => $main_category->term_id,
                'content' => [
                    'description' => $main_category->description,
                    'pages' => $child_posts ?: [],
                    'subcats' => $child_categories ?: []
                ],
                'parent' => $main_category->parent
            ];
        },
        'permission_callback' => '__return_true'
    ]);
});

// Получение категорий V1
add_action('rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/cats/', [
    'methods' => 'GET',
    'callback' => function($data) {
        $my_cats = get_categories( ['exclude' => [0]]);
        foreach($my_cats as $c) {
            $out[] = 
            [
                'name'      => $c->name,
                'id'        => $c->term_id,
                'content'   => [],
                'parent'    => $c->parent,
                'type'      => 'cat'
            ];
        }
        return $out;
    },
        'permission_callback' => function () {
            return true;
        }]);
});

// Получение категорий V2
add_action('rest_api_init', function(){
    register_rest_route('geopoints/v2', '/cats/', [
        'methods' => 'GET',
        'callback' => function() {
            $out = [];
            $categories = get_terms([
                'taxonomy' => 'category',
                'exclude' => [0],
                'hide_empty' => false,
            ]);

            if (is_wp_error($categories)) {
                return [];
            }

            foreach ($categories as $category) {
                $out[] = [
                    'name' => $category->name,
                    'id' => $category->term_id,
                    'parent' => $category->parent
                ];
            }

            return $out;
        },
        'permission_callback' => '__return_true'
    ]);
});

// Экспорт типов работ V1
add_action('rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/worktype', [
    'methods' => 'GET',
    'callback' => function() {
        // запрос у стандартного АПИ
        $request = new WP_REST_Request( 'GET', '/wp/v2/worktype' );
        $out = [];
        $request->set_param( 'per_page', 100 );
        $response = rest_do_request( $request );
        foreach($response->data as $p) {
            $out[] = [
                'name' =>$p['name'],
                'id' =>$p['slug'],
                'color'=>$p['worktype-color']
            ];
        }
        return $out;
    },
        'permission_callback' => function (): bool {
            return true;
        }]);
});

// Экспорт типов работ V2
add_action('rest_api_init', function(){
    register_rest_route('geopoints/v2', '/worktype', [
        'methods' => 'GET',
        'callback' => function() {
            $out = [];
            $terms = get_terms([
                'taxonomy' => 'worktype',
                'hide_empty' => false,
                'number' => 100,
            ]);
            
            if (is_wp_error($terms)) {
                return [];
            }
            
            foreach ($terms as $term) {
                $out[] = [
                    'name' => $term->name,
                    'id' => $term->slug, // Используем slug как ID, как в оригинальном коде
                    'color' => get_term_meta($term->term_id, 'worktype-color', true)
                ];
            }
            
            return $out;
        },
        'permission_callback' => '__return_true'
    ]);
});

// экспорт глубин V1
add_action('rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/deep', [
    'methods' => 'GET',
    'callback' => function() {
        // запрос у стандартного АПИ
        $request = new WP_REST_Request( 'GET', '/wp/v2/deep' );
        $out = [];
        $request->set_param( 'per_page', 100 );
        $response = rest_do_request( $request );
        foreach($response->data as $p) {
            $out[] = [
                'id'   =>$p['id'],
                'name' =>str_replace('&gt;', '>', $p['name']),
                'color'=>$p['deep-color']
            ];
        }
        return $out;
    },
        'permission_callback' => function (): bool {
            return true;
        }]);
});

// Экспорт глубин V2
add_action('rest_api_init', function(){
    register_rest_route('geopoints/v2', '/deep', [
        'methods' => 'GET',
        'callback' => function() {
            $out = [];
            $terms = get_terms([
                'taxonomy' => 'deep',
                'hide_empty' => false,
                'number' => 100,
            ]);

            if (is_wp_error($terms)) {
                return [];
            }

            foreach ($terms as $term) {
                $out[] = [
                    'id' => $term->term_id,
                    'name' => str_replace('&gt;', '>', $term->name),
                    'color' => get_term_meta($term->term_id, 'deep-color', true)
                ];
            }

            return $out;
        },
        'permission_callback' => '__return_true'
    ]);
});

// экспорт руководителей V1
add_action('rest_api_init', function(){
  register_rest_route( 'geopoints/v1', '/headman', [
    'methods' => 'GET',
    'callback' => function() {
        // запрос у стандартного АПИ
        $request = new WP_REST_Request( 'GET', '/wp/v2/headman' );
        $out = [];
        $request->set_param( 'per_page', 100 );
        $response = rest_do_request( $request );
        foreach($response->data as $p) {
            $out[] = [
                'id'   =>$p['id'],
                'name' =>$p['name'],
                'color'=>$p['headman-color']
            ];
        }
        return $out;
    },
        'permission_callback' => function () {
            return true;
        }]);
});

// Экспорт руководителей V2
add_action('rest_api_init', function(){
    register_rest_route('geopoints/v2', '/headman', [
        'methods' => 'GET',
        'callback' => function() {
            $out = [];
            $terms = get_terms([
                'taxonomy' => 'headman',
                'hide_empty' => false,
                'number' => 100, // Ограничение количества элементов
            ]);
            
            if (is_wp_error($terms)) {
                return [];
            }
            
            foreach ($terms as $term) {
                $out[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'color' => get_term_meta($term->term_id, 'headman-color', true)
                ];
            }
            
            return $out;
        },
        'permission_callback' => '__return_true'
    ]);
});