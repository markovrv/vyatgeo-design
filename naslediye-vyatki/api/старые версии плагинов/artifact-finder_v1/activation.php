<?php
// Создаем страницы плагина при активации
register_activation_hook(__FILE__, function() {
    $page = [
        'post_status' => 'publish',
        'post_title' => 'О разделе Этнографическая коллекция',
        'post_name' => 'findings-about',
        'post_type' => 'page',
        'post_content' => '<p>В этом разделе представлена коллекция находок музея. Каждая находка содержит подробное описание, включая материал, происхождение, время создания и другие характеристики.</p>'
    ];
    if (!get_page_by_path('findings-about')) wp_insert_post($page);
});