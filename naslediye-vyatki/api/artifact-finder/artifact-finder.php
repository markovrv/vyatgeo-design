<?php
/*
Plugin Name: Этнографическая коллекция археологической лаборатории
Description: Плагин для управления экспонатами этнографической коллекции археологической лаборатории
*/


// замена заголовка архива
add_action('blocksy:hero:after', function () {
    if (is_archive() && is_post_type_archive('finding') && !is_admin()):
        ?> 
        <style>
            .my-menu {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 12px;
            }
        </style>
        <script>
            document.getElementsByClassName('hero-section')[0].innerHTML = `
                <a href="../findings-about/" class="my-menu">О разделе</a>
                <header class="entry-header">
                    <h1 class="page-title" title="Этнографическая коллекция археологической лаборатории ВятГУ" itemprop="headline">
                        <span class="ct-title-label">Этнографическая коллекция<br> археологической лаборатории ВятГУ</span>
                    </h1>
                </header>`;
        </script>
        <?php
    endif;
});


// Для отображения на странице отдельной находки
add_filter('the_content', function($content) {
    if (is_singular('finding')) {
        return get_finding_detailed_layout($content);
    }
    return $content;
});

function enqueue_finding_assets() {
    if (is_singular('finding')) {
        // Подключаем CSS
        wp_enqueue_style(
            'finding-styles',
            plugin_dir_url(__FILE__) . 'included/css/finding-styles.css',
            array(),
            '1.0.0'
        );
        
        // Подключаем JavaScript
        wp_enqueue_script(
            'finding-gallery',
            plugin_dir_url(__FILE__) . 'included/js/finding-gallery.js',
            array(),
            '1.0.0',
            true // в футере
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_finding_assets');

function get_finding_detailed_layout($original_content) {
    $finding_id = get_the_ID();
    $title = get_the_title();
    
    // Получаем все данные находки
    $data = get_finding_data($finding_id);
    
    ob_start();
    ?>
    <div class="finding-detailed-layout">
        <div class="finding-container">
            <!-- Левая колонка - информация -->
            <div class="finding-info-column">
                <div class="finding-details">
                    <?php echo get_finding_catalog_number($data['catalog_id']); ?>
                    <?php echo get_finding_fields_html($data, $original_content); ?>
                </div>
            </div>
            
            <!-- Правая колонка - галерея -->
            <?php if (!empty($data['additional_images'])): ?>
            <div class="finding-gallery-column">
                <?php echo get_finding_gallery_html($data['additional_images'], $title); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php echo get_finding_styles(); ?>
    <?php echo get_finding_scripts(); ?>
    <?php
    
    return ob_get_clean();
}

function get_finding_data($finding_id) {
    return [
        'dimensions' => get_post_meta($finding_id, 'finding_dimensions', true),
        'catalog_id' => get_post_meta($finding_id, 'finding_cat_id', true),
        'functionality' => get_post_meta($finding_id, 'finding_functionality', true),
        'features' => get_post_meta($finding_id, 'finding_features', true),
        'additional_images' => get_finding_images_urls($finding_id),
        'materials' => get_finding_terms_string($finding_id, 'finding_material'),
        'origin' => get_finding_terms_string($finding_id, 'finding_origin'),
        'receipt_time' => get_finding_terms_string($finding_id, 'finding_receipt_time'),
        'creation_time' => get_finding_terms_string($finding_id, 'finding_creation_time'),
        'type' => get_finding_terms_string($finding_id, 'finding_type')
    ];
}

function get_finding_images_urls($finding_id) {
    $images = [];
    $additional_images_ids = get_post_meta($finding_id, 'finding_additional_images', true);
    
    if (!empty($additional_images_ids) && is_array($additional_images_ids)) {
        foreach ($additional_images_ids as $image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) $images[] = $image_url;
        }
    }
    
    // Добавляем featured image если нет дополнительных
    if (empty($images)) {
        $featured_image = get_the_post_thumbnail_url($finding_id, 'full');
        if ($featured_image) $images[] = $featured_image;
    }
    
    return $images;
}

function get_finding_terms_string($finding_id, $taxonomy) {
    $terms = get_the_terms($finding_id, $taxonomy);
    return $terms ? implode(', ', wp_list_pluck($terms, 'name')) : '';
}

function get_finding_catalog_number($catalog_id) {
    if (!$catalog_id) return '';
    
    return sprintf(
        '<div class="catalog-number-field">
            <div class="field-name">Номер по описи</div>
            <div class="field-value">%s</div>
        </div>',
        esc_html($catalog_id)
    );
}

function get_finding_fields_html($data, $original_content) {
    $fields = [
        'Понятие' => $original_content,
        'Тип' => $data['type'],
        'Функционал' => $data['functionality'],
        'Особенности' => $data['features'],
        'Материал' => $data['materials'],
        'Размеры (ш×д×в) см' => $data['dimensions'],
        'Происхождение' => $data['origin'],
        'Время поступления' => $data['receipt_time'],
        'Время создания' => $data['creation_time']
    ];
    
    $html = '';
    foreach ($fields as $name => $value) {
        if ($value && trim(strip_tags($value))) {
            $html .= sprintf(
                '<div class="field-row">
                    <div class="field-name">%s</div>
                    <div class="field-value">%s</div>
                </div>',
                esc_html($name),
                $name === 'Понятие' ? wpautop($value) : wpautop(esc_html($value))
            );
        }
    }
    
    return $html;
}

function get_finding_gallery_html($images, $title) {
    ob_start();
    ?>
    <div class="gallery-carousel">
        <?php if (count($images) > 1): ?>
        <div class="gallery-thumbnails">
            <?php foreach ($images as $i => $img_url): ?>
            <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>" 
                 onclick="switchFindingImage(this, '<?php echo esc_url($img_url); ?>', <?php echo $i; ?>)">
                <img src="<?php echo esc_url($img_url); ?>" 
                     alt="<?php echo esc_attr($title); ?> - изображение <?php echo $i + 1; ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="gallery-main">
            <img src="<?php echo esc_url($images[0]); ?>" 
                 alt="<?php echo esc_attr($title); ?>">
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function get_finding_styles() {
    return '';
}

function get_finding_scripts() {
    return '';
}


// Объявляем шаблон для просмотра данных
add_filter('template_include', function ($template_path) {
    if( get_post_type() == 'finding' && is_archive() ){

        // Активируем свой шаблон для ячейки архива
        add_filter('blocksy:posts-listing:cards:custom-output', function($card_render) {
            $card_render['has_default_layout'] = true;
            
            $finding_id = get_the_ID();
            $title = get_the_title() ?: 'Безымянная находка №' . $finding_id;
            
            // Получаем основные данные
            $catalog_id = get_post_meta($finding_id, 'finding_cat_id', true);
            $origin = get_the_term_list($finding_id, 'finding_origin', '', ', ');            $materials = get_the_term_list($finding_id, 'finding_material', '', ', ');
            $creation_time = get_the_term_list($finding_id, 'finding_creation_time', '', ', ');
            $type = get_the_term_list($finding_id, 'finding_type', '', ', ');

            $card_render['output'] = '
                <article class="ct-card finding-card-compact">
                    <a class="ct-media-container boundless-image" href="' . get_permalink() . '" aria-label="' . esc_attr($title) . '">
                        ' . get_the_post_thumbnail($finding_id, 'medium', array(
                            'class' => 'attachment-thumbnail size-thumbnail wp-post-image',
                            'loading' => 'lazy', 
                            'decoding' => 'async', 
                            'itemprop' => 'image',
                            'style' => 'aspect-ratio: 4/3;'
                        )) . '
                    </a>
                    
                    <div class="card-content">
                        <!-- Заголовок с номером -->
                        <h2 class="entry-title">
                            <a href="' . get_permalink() . '" rel="bookmark">
                                ' . esc_html($title) . '
                                ' . ($catalog_id ? '<span class="catalog-badge">' . esc_html($catalog_id) . '</span>' : '') . '
                            </a>
                        </h2>
                        
                        <!-- Основная информация в одну строку -->
                        <div class="finding-meta-compact">
                            ' . ($type ? '<span class="meta-item type">' . $type . '</span>' : '') . '
                            ' . ($materials ? '<span class="meta-item material">' . $materials . '</span>' : '') . '
                            ' . ($creation_time ? '<span class="meta-item creation-time">' . $creation_time . '</span>' : '') . '
                            ' . ($origin ? '<span class="meta-item origin">' . $origin . '</span>' : '') . '
                        </div>
                    </div>
                </article>';
            
            return $card_render;
        }, 1);

    }
    return $template_path;
}, 1);

// скрипт активации плагина
include('activation.php');

// регистрируем кастомный тип данных
include('register-findings.php');

// регистрируем АПИ для импорта и экспорта данных
include('rest-api.php');