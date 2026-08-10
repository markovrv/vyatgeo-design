<?php
/**
 * Админ-страница «Строки интерфейса» (Внешний вид → Строки интерфейса).
 *
 * Позволяет редактору подменять любую надпись SPA-фронтенда без правки кода —
 * как и WordPress позволяет менять название сайта, не трогая wp-config.php.
 *
 * В основе лежит src/i18n/strings.json из Vue-проекта (копируется в тему
 * скриптом build:theme). PHP читает дефолтные значения из этого файла,
 * накладывает сохранённые редактором переопределения (nv_i18n_strings) и
 * отдаёт их в SPA через window.__I18N_STRINGS__ (см. functions.php).
 *
 * При первом открытии страницы ни одного переопределения ещё нет — форма
 * показывает поля с плейсхолдерами-дефолтами. На практике редактор меняет
 * только несколько строк; остальные остаются пустыми и SPA использует
 * дефолтное значение из своего бандла — именно так задумано, а не
 * копированием всех 200+ строк в базу.
 */

add_action('admin_menu', 'nv_i18n_strings_register_page');
add_action('admin_post_nv_i18n_strings_save', 'nv_i18n_strings_save');

function nv_i18n_strings_register_page() {
    add_theme_page(
        'Строки интерфейса',
        'Строки интерфейса',
        'edit_theme_options',
        'nv-i18n-strings',
        'nv_i18n_strings_render_page'
    );
}

function nv_i18n_strings_defaults() {
    $file = get_template_directory() . '/i18n/strings.json';
    if (!is_file($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Разворачивает вложенный JSON в плоский список ключ → дефолтное_значение.
 * Ключи — dot-пути вроде "common.loading", "views.IndexView.heroTitle".
 */
function nv_i18n_strings_flatten($data, $prefix = '') {
    $result = [];
    foreach ($data as $key => $value) {
        $path = $prefix ? "$prefix.$key" : $key;
        if (is_array($value) && !isset($value[0])) {
            $result = array_merge($result, nv_i18n_strings_flatten($value, $path));
        } else {
            $result[$path] = $value;
        }
    }
    return $result;
}

/**
 * Группировка первого уровня: common, layouts, components, views, composables.
 */
function nv_i18n_strings_sections() {
    $defaults = nv_i18n_strings_defaults();
    $sections = [];
    foreach ($defaults as $section => $content) {
        if (!is_array($content)) continue;
        $sections[$section] = [
            'label'   => nv_i18n_section_label($section),
            'flatten' => nv_i18n_strings_flatten($content, $section),
        ];
    }
    return $sections;
}

function nv_i18n_section_label($key) {
    $labels = [
        'common'       => 'Общие',
        'layouts'      => 'Макеты (шапка / футер)',
        'components'   => 'Компоненты',
        'views'        => 'Страницы',
        'composables'  => 'Композаблы',
    ];
    return $labels[$key] ?? $key;
}

function nv_i18n_strings_render_page() {
    $sections = nv_i18n_strings_sections();
    $overrides = get_option('nv_i18n_strings', []);

    $saved = isset($_GET['saved']);
    ?>
    <div class="wrap">
        <h1>Строки интерфейса</h1>
        <p>Здесь можно изменить любую текстовую надпись, которую видят посетители сайта — кнопки, заголовки, подсказки, сообщения об ошибках. Оставьте поле пустым, чтобы использовать значение по умолчанию.</p>

        <?php if ($saved): ?>
            <div class="notice notice-success is-dismissible"><p>Настройки сохранены. Изменения видны сразу на сайте (перезагрузите страницу).</p></div>
        <?php endif; ?>

        <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <input type="hidden" name="action" value="nv_i18n_strings_save">
            <?php wp_nonce_field('nv_i18n_strings_save', 'nv_i18n_nonce'); ?>

            <div id="nv-i18n-tabs" style="margin-top:16px;">
                <div class="nav-tab-wrapper">
                    <?php $first = true; foreach ($sections as $id => $sec): ?>
                        <a href="#tab-<?= esc_attr($id) ?>" class="nav-tab <?= $first ? 'nav-tab-active' : '' ?>"><?= esc_html($sec['label']) ?></a>
                        <?php $first = false; endforeach; ?>
                </div>

                <?php $first = true; foreach ($sections as $id => $sec): ?>
                    <div id="tab-<?= esc_attr($id) ?>" class="nv-i18n-tab" style="<?= $first ? '' : 'display:none;' ?> padding-top:16px; max-width:800px;">
                        <table class="form-table">
                            <?php foreach ($sec['flatten'] as $path => $default): ?>
                                <?php $current = $overrides[$path] ?? ''; ?>
                                <tr>
                                    <th scope="row">
                                        <label for="i18n_<?= esc_attr($path) ?>">
                                            <code style="font-size:11px; display:block; word-break:break-all;"><?= esc_html($path) ?></code>
                                        </label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="i18n_<?= esc_attr($path) ?>"
                                            name="i18n[<?= esc_attr($path) ?>]"
                                            value="<?= esc_attr($current) ?>"
                                            placeholder="<?= esc_attr(is_string($default) ? $default : '') ?>"
                                            class="regular-text"
                                            style="width:100%; max-width:600px;"
                                        />
                                        <?php if (!$current && is_string($default)): ?>
                                            <p class="description" style="margin-top:2px; color:#666;">
                                                По&nbsp;умолчанию: <?= esc_html(mb_substr($default, 0, 120)) ?><?= mb_strlen($default) > 120 ? '…' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <?php $first = false; endforeach; ?>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary">Сохранить изменения</button>
            </p>
        </form>
    </div>

    <script>
    // Табы без подгрузки jQuery UI — нативный переключатель.
    (function () {
        var tabs = document.getElementById('nv-i18n-tabs');
        var links = tabs.querySelectorAll('.nav-tab');
        links.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var targetId = this.getAttribute('href').replace('#tab-', '');
                tabs.querySelectorAll('.nav-tab').forEach(function (l) { l.classList.remove('nav-tab-active'); });
                this.classList.add('nav-tab-active');
                tabs.querySelectorAll('.nv-i18n-tab').forEach(function (t) { t.style.display = 'none'; });
                document.getElementById('tab-' + targetId).style.display = '';
            });
        });
    })();
    </script>
    <?php
}

function nv_i18n_strings_save() {
    if (!current_user_can('edit_theme_options')) wp_die('Недостаточно прав.');

    check_admin_referer('nv_i18n_strings_save', 'nv_i18n_nonce');

    $raw = $_POST['i18n'] ?? [];
    $overrides = [];

    foreach ($raw as $path => $value) {
        $path = sanitize_text_field($path);
        $value = wp_kses_post(wp_unslash($value));
        $trimmed = trim($value);
        if ($trimmed !== '') {
            $overrides[$path] = $trimmed;
        }
    }

    update_option('nv_i18n_strings', $overrides, false);

    wp_redirect(add_query_arg('saved', '1', wp_get_referer()));
    exit;
}
