<?php

defined('ABSPATH') || exit;

final class WYA_Admin {
    private const OPTION = 'wya_menu_settings';

    public static function get_settings(): array {
        $defaults = [
            'aria_label' => 'WalkingYog animated menu',
            'media' => [
                'url' => '',
                'type' => 'gif',
                'pivot_x' => 50,
                'pivot_y' => 50,
            ],
            'interaction' => [
                'hover_radius' => 180,
                'max_tilt' => 7,
            ],
            'items' => [],
        ];
        $saved = get_option(self::OPTION, []);
        return wp_parse_args($saved, $defaults);
    }

    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'settings']);
    }

    public static function menu(): void {
        add_options_page(
            'WalkingYog Animated Menu',
            'WalkingYog Animated Menu',
            'manage_options',
            'walkingyog-animated-menu',
            [self::class, 'page']
        );
    }

    public static function settings(): void {
        register_setting('wya_menu', self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
        ]);
    }

    public static function sanitize($input): array {
        $current = self::get_settings();
        $out = $current;
        $out['aria_label'] = sanitize_text_field($input['aria_label'] ?? $current['aria_label']);
        $out['media']['url'] = esc_url_raw($input['media']['url'] ?? '');
        $out['media']['type'] = in_array(($input['media']['type'] ?? 'gif'), ['gif', 'mp4'], true) ? $input['media']['type'] : 'gif';
        $out['media']['pivot_x'] = max(0, min(100, (float) ($input['media']['pivot_x'] ?? 50)));
        $out['media']['pivot_y'] = max(0, min(100, (float) ($input['media']['pivot_y'] ?? 50)));
        $out['interaction']['hover_radius'] = max(40, min(600, (float) ($input['interaction']['hover_radius'] ?? 180)));
        $out['interaction']['max_tilt'] = max(0, min(20, (float) ($input['interaction']['max_tilt'] ?? 7)));

        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $out['items'] = [];
        foreach ($items as $item) {
            $url = esc_url_raw($item['url'] ?? '');
            $title = sanitize_text_field($item['title'] ?? '');
            if ($url === '' || $title === '') {
                continue;
            }
            $out['items'][] = [
                'title' => $title,
                'url' => $url,
                'description' => sanitize_text_field($item['description'] ?? ''),
                'icon' => esc_url_raw($item['icon'] ?? ''),
                'preview' => esc_url_raw($item['preview'] ?? ''),
                'external' => !empty($item['external']) ? 1 : 0,
                'new_tab' => !empty($item['new_tab']) ? 1 : 0,
                'enabled' => !isset($item['enabled']) || !empty($item['enabled']) ? 1 : 0,
                'x' => max(-50, min(50, (float) ($item['x'] ?? 0))),
                'y' => max(-50, min(50, (float) ($item['y'] ?? 0))),
                'z' => max(-50, min(50, (float) ($item['z'] ?? 0))),
            ];
        }
        return $out;
    }

    public static function page(): void {
        if (!current_user_can('manage_options')) return;
        $s = self::get_settings();
        ?>
        <div class="wrap wya-admin">
            <h1>WalkingYog Animated Menu</h1>
            <p>Шорткод: <code>[wyg_animated_menu]</code>. Центральный media-файл может быть GIF или MP4. Pivot — точка, вокруг которой голова/объект реагирует на направление меню.</p>
            <form method="post" action="options.php">
                <?php settings_fields('wya_menu'); ?>
                <h2>Центральный объект</h2>
                <table class="form-table" role="presentation">
                    <tr><th>GIF / MP4 URL</th><td><input class="regular-text" name="wya_menu_settings[media][url]" value="<?php echo esc_attr($s['media']['url']); ?>"></td></tr>
                    <tr><th>Тип</th><td><select name="wya_menu_settings[media][type]"><option value="gif" <?php selected($s['media']['type'],'gif'); ?>>GIF</option><option value="mp4" <?php selected($s['media']['type'],'mp4'); ?>>MP4</option></select></td></tr>
                    <tr><th>Pivot X</th><td><input type="number" min="0" max="100" step="0.1" name="wya_menu_settings[media][pivot_x]" value="<?php echo esc_attr($s['media']['pivot_x']); ?>"> % по ширине</td></tr>
                    <tr><th>Pivot Y</th><td><input type="number" min="0" max="100" step="0.1" name="wya_menu_settings[media][pivot_y]" value="<?php echo esc_attr($s['media']['pivot_y']); ?>"> % по высоте</td></tr>
                    <tr><th>Радиус реакции</th><td><input type="number" min="40" max="600" step="1" name="wya_menu_settings[interaction][hover_radius]" value="<?php echo esc_attr($s['interaction']['hover_radius']); ?>"> px</td></tr>
                    <tr><th>Макс. наклон</th><td><input type="number" min="0" max="20" step="0.1" name="wya_menu_settings[interaction][max_tilt]" value="<?php echo esc_attr($s['interaction']['max_tilt']); ?>"> °</td></tr>
                </table>

                <h2>Пункты меню</h2>
                <p>Для каждого пункта можно указать внутреннюю или внешнюю ссылку и собственный preview. Координаты — проценты относительно сцены; Z отвечает за глубину.</p>
                <table class="widefat striped">
                    <thead><tr><th>Название</th><th>URL</th><th>Preview</th><th>X</th><th>Y</th><th>Z</th><th>Внешняя</th><th>Новое окно</th><th>Активен</th></tr></thead>
                    <tbody>
                    <?php foreach ($s['items'] as $i => $item): ?>
                        <tr>
                            <td><input name="wya_menu_settings[items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($item['title']); ?>"></td>
                            <td><input size="28" name="wya_menu_settings[items][<?php echo $i; ?>][url]" value="<?php echo esc_attr($item['url']); ?>"></td>
                            <td><input size="24" name="wya_menu_settings[items][<?php echo $i; ?>][preview]" value="<?php echo esc_attr($item['preview']); ?>"></td>
                            <td><input type="number" step="0.1" name="wya_menu_settings[items][<?php echo $i; ?>][x]" value="<?php echo esc_attr($item['x']); ?>"></td>
                            <td><input type="number" step="0.1" name="wya_menu_settings[items][<?php echo $i; ?>][y]" value="<?php echo esc_attr($item['y']); ?>"></td>
                            <td><input type="number" step="0.1" name="wya_menu_settings[items][<?php echo $i; ?>][z]" value="<?php echo esc_attr($item['z']); ?>"></td>
                            <td><input type="checkbox" name="wya_menu_settings[items][<?php echo $i; ?>][external]" value="1" <?php checked($item['external'],1); ?>></td>
                            <td><input type="checkbox" name="wya_menu_settings[items][<?php echo $i; ?>][new_tab]" value="1" <?php checked($item['new_tab'],1); ?>></td>
                            <td><input type="checkbox" name="wya_menu_settings[items][<?php echo $i; ?>][enabled]" value="1" <?php checked($item['enabled'],1); ?>></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="wya-template-row">
                        <td colspan="9"><em>Для первого MVP добавь пункты через импорт JSON из docs/example-menu.json; GUI-repeater будет следующим шагом.</em></td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button('Сохранить меню'); ?>
            </form>
        </div>
        <?php
    }
}

WYA_Admin::init();
