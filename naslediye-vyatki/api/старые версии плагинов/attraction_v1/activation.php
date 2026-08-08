<?php
register_activation_hook( dirname(__FILE__).'/attraction.php', function() {
    $page = [
        'post_status' => 'publish' ,
        'post_title' => 'Архитектура и скульптура г. Кирова: Прошлое и современность',
        'post_name' => 'attraction-map',
        'post_type' => 'page',
        'post_content' => 'Страница не требует редактирования. Весь контент настраивается в шаблоне плагина Археологические памятники Кирова.'
    ];
    if (!get_page_by_path( 'attraction-map' )) wp_insert_post( $page );

});


// Проверяем зависимости плагина
add_action('admin_init', function () {
    if (is_plugin_active('yamaps/yamap.php'))
        return;
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php _e('attraction: Активируйте плагин "Yamaps" для использования карт Геобазы.', 'your-text-domain'); ?>
            </p>
        </div>
        <?php
    });
});