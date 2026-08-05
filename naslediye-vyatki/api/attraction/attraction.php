<?php
/* 
Plugin Name: Архитектура и скульптура г. Кирова: Прошлое и современность
*/

// Объявляем шаблон для просмотра данных
add_filter('template_include', function ($template_path) {
    if (get_post_type() == 'attraction' && is_single()) {
        // Активируем свой шаблон для одиночной записи
        return plugin_dir_path(file: __FILE__) . 'templates/single-attraction.php';

    } elseif( is_page('attraction-map') ){
        // Активируем SPA
        wp_enqueue_script( 'vue1', plugin_dir_url(__FILE__) . 'vue/index.js',[],'1.4',['type' => 'module', 'strategy' => 'defer', 'crossorigin' => 'anonymous'] );
        wp_enqueue_style( 'vue2', plugin_dir_url(__FILE__) . 'vue/index.css?1.4' );
        return plugin_dir_path(__FILE__) . 'templates/template_for_yamap.php';
    }
    return $template_path;
}, 1);

// процедуры активации плагина (создание страницы с картой, проверка зависимого плагина)
include("activation.php");

 // регистрация своего типа записей и связаных с ним таксономий
 include('register-attraction.php');

 // регистрируем АПИ для импорта и экспорта данных
include('rest-api.php');