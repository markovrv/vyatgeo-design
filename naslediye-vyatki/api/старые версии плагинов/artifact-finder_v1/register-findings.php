<?php
// Регистрируем пользовательский тип данных "Находки"
add_action('init', function () {
    register_post_type('finding', [
        'labels' => [
            'name' => 'Этнографическая коллекция',
            'singular_name' => 'Экспонат',
            'add_new' => 'Добавить экспонат',
            'add_new_item' => 'Добавить новый экспонат',
            'edit' => 'Изменить',
            'edit_item' => 'Изменить экспонат',
            'new_item' => 'Новый экспонат',
            'view' => 'Просмотреть',
            'view_item' => 'Просмотреть экспонат',
            'search_items' => 'Поиск экспонатов',
            'not_found' => 'Экспонаты не найдены',
            'not_found_in_trash' => 'Корзина пуста',
            'parent' => 'Родительский экспонат'
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_position' => 16,
        'supports' => ['title', 'editor', 'thumbnail'],
        'taxonomies' => ['finding_material', 'finding_origin', 'finding_receipt_time', 'finding_creation_time'],
        'menu_icon' => "dashicons-search",
        'has_archive' => true
    ]);

    // Регистрируем таксономии
    register_taxonomy('finding_material', 'finding', [
        'labels' => [
            'name' => 'Материалы',
            'singular_name' => 'Материал',
            'search_items' => 'Поиск материалов',
            'all_items' => 'Все материалы',
            'edit_item' => 'Изменить материал',
            'update_item' => 'Обновить материал',
            'add_new_item' => 'Добавить новый материал',
            'new_item_name' => 'Название нового материала',
            'menu_name' => 'Материалы'
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true
    ]);

    register_taxonomy('finding_origin', 'finding', [
        'labels' => [
            'name' => 'Происхождение',
            'singular_name' => 'Происхождение',
            'search_items' => 'Поиск по происхождению',
            'all_items' => 'Все места происхождения',
            'edit_item' => 'Изменить происхождение',
            'update_item' => 'Обновить происхождение',
            'add_new_item' => 'Добавить новое происхождение',
            'new_item_name' => 'Новое происхождение',
            'menu_name' => 'Происхождение'
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true
    ]);

    register_taxonomy('finding_receipt_time', 'finding', [
        'labels' => [
            'name' => 'Время поступления',
            'singular_name' => 'Время поступления',
            'search_items' => 'Поиск по времени поступления',
            'all_items' => 'Все периоды поступления',
            'edit_item' => 'Изменить время поступления',
            'update_item' => 'Обновить время поступления',
            'add_new_item' => 'Добавить время поступления',
            'new_item_name' => 'Новое время поступления',
            'menu_name' => 'Время поступления'
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true
    ]);

    register_taxonomy('finding_creation_time', 'finding', [
        'labels' => [
            'name' => 'Время создания',
            'singular_name' => 'Время создания',
            'search_items' => 'Поиск по времени создания',
            'all_items' => 'Все периоды создания',
            'edit_item' => 'Изменить время создания',
            'update_item' => 'Обновить время создания',
            'add_new_item' => 'Добавить время создания',
            'new_item_name' => 'Новое время создания',
            'menu_name' => 'Время создания'
        ],
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true
    ]);
});

// Добавляем метаполя в REST API
add_action('rest_api_init', function () {
    // Размеры
    register_rest_field('finding', 'finding_dimensions', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_post_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);

    // Номер в каталоге
    register_rest_field('finding', 'finding_cat_id', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_post_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);

    // Функционал
    register_rest_field('finding', 'finding_functionality', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_post_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);

    // Особенности
    register_rest_field('finding', 'finding_features', [
        'get_callback' => function ($object, $field_name, $request) {
            return get_post_meta($object["id"], $field_name, true);
        },
        'update_callback' => function ($value, $object, $field_name) {
            if (!$value) return;
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);

    // Дополнительные изображения
    register_rest_field('finding', 'finding_additional_images', [
        'get_callback' => function ($object, $field_name, $request) {
            $images = get_post_meta($object["id"], $field_name, true);
            return $images ? $images : [];
        },
        'update_callback' => function ($value, $object, $field_name) {
            return update_post_meta($object->ID, $field_name, $value);
        },
        'schema' => null
    ]);
});

// Отключаем Gutenberg для типа "Экспонат"
add_filter('use_block_editor_for_post_type', function ($current_status, $post_type) {
    if ($post_type === 'finding') return false;
    return $current_status;
}, 10, 2);

// Добавляем метабоксы в админку
add_action('admin_init', function () {
    add_meta_box(
        'finding_meta_box',
        'Характеристики экспоната',
        'display_finding_meta_box',
        'finding',
        'normal',
        'high'
    );

    add_meta_box(
        'finding_images_box',
        'Изображения экспоната',
        'display_finding_images_box',
        'finding',
        'normal',
        'high'
    );
});

function display_finding_meta_box($finding) {
    wp_nonce_field('finding_meta_nonce', 'finding_meta_nonce');
    
    $cat_id = esc_html(get_post_meta($finding->ID, 'finding_cat_id', true));
    $dimensions = esc_html(get_post_meta($finding->ID, 'finding_dimensions', true));
    $functionality = esc_html(get_post_meta($finding->ID, 'finding_functionality', true));
    $features = esc_html(get_post_meta($finding->ID, 'finding_features', true));
    ?>
    <table style="width: 100%;">
        <tr>
            <td><label for="finding_cat_id">Номер в каталоге:</label></td>
            <td><input type="text" style="width: 100%;margin-bottom: 12px;" id="finding_cat_id" name="finding_cat_id" value="<?= $cat_id; ?>" /></td>
        </tr>
        <tr>
            <td style="width: 20%;"><label for="finding_dimensions">Размеры (ш×д×в) см:</label></td>
            <td><input type="text" style="width: 100%;margin-bottom: 12px;" id="finding_dimensions" name="finding_dimensions" value="<?= $dimensions; ?>" /></td>
        </tr>
        <tr>
            <td><label for="finding_functionality">Функционал:</label></td>
            <td><textarea style="width: 100%;margin-bottom: 12px; height: 80px;" id="finding_functionality" name="finding_functionality"><?= $functionality; ?></textarea></td>
        </tr>
        <tr>
            <td><label for="finding_features">Особенности:</label></td>
            <td><textarea style="width: 100%;margin-bottom: 12px; height: 120px;" id="finding_features" name="finding_features"><?= $features; ?></textarea></td>
        </tr>
    </table>
    <?php
}

function display_finding_images_box($finding) {
    wp_nonce_field('finding_images_nonce', 'finding_images_nonce');
    
    $additional_images = get_post_meta($finding->ID, 'finding_additional_images', true);
    $additional_images = $additional_images ? $additional_images : [];
    ?>
    <div class="finding-images-info" style="background: #f0f0f1; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
        <p style="margin: 0; color: #3c434a;">
            <strong>💡 Подсказка:</strong> Перетаскивайте изображения для изменения порядка. Первое изображение будет установлено как основное изображение экспоната.
        </p>
    </div>
    
    <div id="finding-additional-images-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-bottom: 15px;">
        <?php foreach ($additional_images as $index => $image_id): 
            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
            if (!$image_url) continue;
        ?>
            <div class="image-field draggable-image" data-image-id="<?= $image_id; ?>" style="position: relative; display: inline-block; cursor: move;">
                <input type="hidden" name="finding_additional_images[]" value="<?= $image_id; ?>" />
                <div class="image-wrapper" style="position: relative; overflow: hidden; border-radius: 8px;">
                    <img src="<?= $image_url; ?>" style="width: 100%; height: 150px; object-fit: cover;" />
                    <div class="image-number" style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                        <?= $index + 1; ?>
                    </div>
                    <button type="button" class="remove-image" style="padding-bottom: 3px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0); width: 40px; height: 40px; border: none; border-radius: 50%; background: #dc3232; color: white; cursor: pointer; font-size: 20px; font-weight: bold; line-height: 1; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: all 0.3s ease; opacity: 0; z-index: 10;">
                        ×
                    </button>
                    <div class="image-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); opacity: 0; transition: all 0.3s ease; border-radius: 8px;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <button type="button" class="button button-primary" id="add-image-btn" style="margin-top: 10px;">
        Добавить изображение
    </button>

    <style>
    .image-wrapper:hover .remove-image {
        transform: translate(-50%, -50%) scale(1) !important;
        opacity: 1 !important;
    }
    
    .image-wrapper:hover .image-overlay {
        opacity: 1 !important;
    }
    
    .image-wrapper:hover img {
        filter: brightness(0.7);
    }
    
    .remove-image:hover {
        background: #a00 !important;
        transform: translate(-50%, -50%) scale(1.1) !important;
    }
    
    #finding-additional-images-container .image-field {
        transition: transform 0.2s ease;
    }
    
    #finding-additional-images-container .image-field:hover {
        transform: translateY(-2px);
    }
    
    /* Стили для drag & drop */
    .draggable-image.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }
    
    .draggable-image.drag-over {
        border: 2px dashed #007cba;
    }
    
    #finding-additional-images-container.sortable-active {
        min-height: 200px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let dragSrcElement = null;
        
        // Функция для обновления номеров изображений
        function updateImageNumbers() {
            $('#finding-additional-images-container .image-field').each(function(index) {
                $(this).find('.image-number').text(index + 1);
            });
        }
        
        // Инициализация drag & drop
        function initDragAndDrop() {
            const container = document.getElementById('finding-additional-images-container');
            const images = container.getElementsByClassName('draggable-image');
            
            // События для перетаскивания
            Array.from(images).forEach(image => {
                image.setAttribute('draggable', true);
                
                image.addEventListener('dragstart', function(e) {
                    dragSrcElement = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.innerHTML);
                });
                
                image.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                    Array.from(images).forEach(img => {
                        img.classList.remove('drag-over');
                    });
                });
                
                image.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    return false;
                });
                
                image.addEventListener('dragenter', function(e) {
                    this.classList.add('drag-over');
                });
                
                image.addEventListener('dragleave', function() {
                    this.classList.remove('drag-over');
                });
                
                image.addEventListener('drop', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    if (dragSrcElement != this) {
                        container.insertBefore(dragSrcElement, this);
                        updateImageNumbers();
                    }
                    
                    this.classList.remove('drag-over');
                    return false;
                });
            });
        }
        
        // Инициализация при загрузке
        initDragAndDrop();
        updateImageNumbers();
        
        $('#add-image-btn').click(function() {
            var frame = wp.media({
                title: 'Выберите изображение',
                multiple: false,
                library: { type: 'image' },
                button: { text: 'Выбрать' }
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var container = $('#finding-additional-images-container');
                
                var imageField = $(
                    '<div class="image-field draggable-image" data-image-id="' + attachment.id + '" style="position: relative; display: inline-block; cursor: move;">' +
                    '<input type="hidden" name="finding_additional_images[]" value="' + attachment.id + '" />' +
                    '<div class="image-wrapper" style="position: relative; overflow: hidden; border-radius: 8px;">' +
                    '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 100%; height: 150px; object-fit: cover;" />' +
                    '<div class="image-number" style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.7); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">' + (container.children().length + 1) + '</div>' +
                    '<button type="button" class="remove-image" style="padding-bottom: 3px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0); width: 40px; height: 40px; border: none; border-radius: 50%; background: #dc3232; color: white; cursor: pointer; font-size: 20px; font-weight: bold; line-height: 1; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: all 0.3s ease; opacity: 0; z-index: 10;">×</button>' +
                    '<div class="image-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); opacity: 0; transition: all 0.3s ease; border-radius: 8px;"></div>' +
                    '</div>' +
                    '</div>'
                );
                
                container.append(imageField);
                imageField.hide().fadeIn(300);
                
                // Переинициализация drag & drop для нового элемента
                initDragAndDrop();
            });
            
            frame.open();
        });
        
        $(document).on('click', '.remove-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var imageField = $(this).closest('.image-field');
            imageField.fadeOut(300, function() {
                $(this).remove();
                updateImageNumbers();
            });
        });
    });
    </script>
    <?php
}

// Сохранение метаполей
add_action('save_post', function ($finding_id, $finding) {
    if ($finding->post_type != 'finding') return;
    
    // Проверяем nonce
    if (!isset($_POST['finding_meta_nonce']) || !wp_verify_nonce($_POST['finding_meta_nonce'], 'finding_meta_nonce')) {
        return;
    }
    
    // Сохраняем основные метаполя
    $fields = ['finding_dimensions', 'finding_cat_id', 'finding_functionality', 'finding_features'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($finding_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Сохраняем дополнительные изображения
    if (isset($_POST['finding_additional_images'])) {
        $images = array_map('intval', $_POST['finding_additional_images']);
        $images = array_filter($images); // Удаляем пустые значения
        
        update_post_meta($finding_id, 'finding_additional_images', $images);
        
        // Устанавливаем первое изображение как featured image
        if (!empty($images) && is_array($images)) {
            $first_image_id = $images[0];
            
            // Проверяем, что изображение существует
            if (wp_attachment_is_image($first_image_id)) {
                set_post_thumbnail($finding_id, $first_image_id);
            }
        }
    } else {
        update_post_meta($finding_id, 'finding_additional_images', []);
        
        // Если нет дополнительных изображений, удаляем featured image
        delete_post_thumbnail($finding_id);
    }
}, 10, 2);