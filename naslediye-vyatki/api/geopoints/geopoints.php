<?php
/* 
Plugin Name: Геобаза археологических изысканий
*/

// cors policy
add_action('init',function() { header('Access-Control-Allow-Origin: *'); });

// Объявляем шаблон для просмотра данных
add_filter('template_include', function ($template_path) {
    if (get_post_type() == 'geopoints' && is_single()) {
        // Активируем свой шаблон для одиночной работы
        return plugin_dir_path(__FILE__) . 'templates/single-geopoints.php';
    } elseif( get_post_type() == 'geopoints' && is_archive() ){

        // Активируем свой шаблон для одиночной работы
        add_filter('blocksy:posts-listing:cards:custom-output', function($card_render){
            $card_render['has_default_layout'] = true;
            $card_render['output'] = '
                <a class="ct-media-container boundless-image" href="'.get_permalink().'" aria-label="'.((get_the_title())?get_the_title():'Безымянная работа №'.get_the_ID()).'">
                    '.get_the_post_thumbnail(get_the_ID(), 'medium', array('class' => 'attachment-thumbnail size-thumbnail wp-post-image', 'loading' => 'lazy', 'decoding' => 'async', 'itemprop' => 'image', 'style' => 'aspect-ratio: 4/3;')).'
                </a>
                <div class="card-content">
                    <ul class="entry-meta" data-type="simple:circle" data-id="meta_1">
                        <li class="meta-categories" data-type="simple">
                            '.get_the_term_list( get_the_ID(), 'workdate', '', ', ').'
                        </li>
                        <li class="meta-categories" data-type="simple">
                            '.get_the_term_list( get_the_ID(), 'worktype').'
                        </li>
                        <li class="meta-categories" data-type="simple">
                            '.get_the_term_list( get_the_ID(), 'headman').'
                        </li>
                    </ul>
                    <h2 class="entry-title">
                        <a href="'.get_permalink().'" rel="bookmark">
                            '.((get_the_title())?get_the_title():'Безымянная работа №'.get_the_ID()).'
                        </a>
                    </h2>
                    <div class="ct-ghost"><b>Адрес:</b> '.get_post_meta( get_the_ID(), 'address', true).'</div>
                </div>';
            return $card_render;
        }, 1);

    } elseif( is_page('map') ){
        // Активируем SPA
        wp_enqueue_script( 'vue1', plugin_dir_url(__FILE__) . 'vue/js/chunk-vendors.js',[],'1.0',['strategy' => 'defer'] );
        wp_enqueue_script( 'vue2', plugin_dir_url(__FILE__) . 'vue/js/app.js',[],'1.0',['strategy' => 'defer'] );
        wp_enqueue_style( 'vue3', plugin_dir_url(__FILE__) . 'vue/css/app.css' );
        return plugin_dir_path(__FILE__) . 'templates/template_for_yamap.php';
    }
    return $template_path;
}, 1);

// процедуры активации плагина (создание страницы с картой, проверка зависимого плагина)
include('activation.php');

 // регистрация своего типа записей и связаных с ним таксономий
include('register-geopoints.php');

// регистрируем АПИ для импорта и экспорта данных
include('rest-api.php');

// регистрируем виджет для отображения полей таксономии в меню сайта
include('taxonomy-widget.php');