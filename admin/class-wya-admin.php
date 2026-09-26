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
                'z' => max(-300, min(300, (float) ($item['z'] ?? 0))),
                'orbit_radius' => max(0, min(50, (float) ($item['orbit_radius'] ?? 0))),
                'orbit_angle' => max(-360, min(360, (float) ($item['orbit_angle'] ?? 0))),
                'orbit_speed' => max(0, min(2, (float) ($item['orbit_speed'] ?? 0))),
                'orbit_amplitude' => max(0, min(50, (float) ($item['orbit_amplitude'] ?? 0))),
                'phase' => max(-360, min(360, (float) ($item['phase'] ?? 0))),
                'scale' => max(0.5, min(3, (float) ($item['scale'] ?? 1))),
            ];
        }
        return $out;
    }

    private static function field(string $name, $value, string $type = 'text', array $attrs = []): string {
        $attr = '';
        foreach ($attrs as $key => $val) {
            $attr .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
        }
        return '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $attr . '>';
    }

    public static function page(): void {
        if (!current_user_can('manage_options')) return;
        $s = self::get_settings();
        ?>
        <div class="wrap wya-admin">
            <h1>WalkingYog Animated Menu</h1>
            <p><strong>Шорткод:</strong> <code>[wyg_animated_menu]</code>. Сначала задаём центральную голову, затем добавляем летающие пункты и их ссылки.</p>
            <form method="post" action="options.php">
                <?php settings_fields('wya_menu'); ?>

                <h2>Центральный объект</h2>
                <table class="form-table" role="presentation">
                    <tr><th>GIF / MP4</th><td>
                        <div class="wya-media-control">
                            <input class="regular-text wya-media-url" name="wya_menu_settings[media][url]" value="<?php echo esc_attr($s['media']['url']); ?>">
                            <button type="button" class="button wya-media-pick" data-target="wya_menu_settings[media][url]">Выбрать из медиатеки</button>
                        </div>
                    </td></tr>
                    <tr><th>Тип</th><td><select name="wya_menu_settings[media][type]"><option value="gif" <?php selected($s['media']['type'],'gif'); ?>>GIF</option><option value="mp4" <?php selected($s['media']['type'],'mp4'); ?>>MP4</option></select></td></tr>
                    <tr><th>Pivot X</th><td><?php echo self::field('wya_menu_settings[media][pivot_x]', $s['media']['pivot_x'], 'number', ['min'=>0,'max'=>100,'step'=>'0.1']); ?> %</td></tr>
                    <tr><th>Pivot Y</th><td><?php echo self::field('wya_menu_settings[media][pivot_y]', $s['media']['pivot_y'], 'number', ['min'=>0,'max'=>100,'step'=>'0.1']); ?> %</td></tr>
                    <tr><th>Радиус реакции</th><td><?php echo self::field('wya_menu_settings[interaction][hover_radius]', $s['interaction']['hover_radius'], 'number', ['min'=>40,'max'=>600,'step'=>'1']); ?> px</td></tr>
                    <tr><th>Макс. наклон</th><td><?php echo self::field('wya_menu_settings[interaction][max_tilt]', $s['interaction']['max_tilt'], 'number', ['min'=>0,'max'=>20,'step'=>'0.1']); ?> °</td></tr>
                </table>

                <h2>Летающие пункты</h2>
                <p>У каждого пункта есть <strong>ссылка</strong> и отдельные параметры полёта. X/Y/Z — стартовая позиция, радиус/угол/скорость/амплитуда — движение вокруг неё.</p>
                <div id="wya-items" class="wya-items">
                    <?php foreach ($s['items'] as $i => $item): self::render_item($i, $item); endforeach; ?>
                </div>
                <p><button type="button" class="button button-secondary" id="wya-add-item">+ Добавить летающий пункт</button></p>
                <p class="description">Можно использовать внутренний URL WordPress или внешний URL. Для внешней ссылки включи «Внешняя»; «Новое окно» добавляет target=_blank.</p>

                <?php submit_button('Сохранить меню'); ?>
            </form>
        </div>
        <script type="text/html" id="tmpl-wya-item">
            <?php self::render_item('__INDEX__', [
                'title'=>'','url'=>'','description'=>'','icon'=>'','preview'=>'','external'=>0,'new_tab'=>0,'enabled'=>1,
                'x'=>0,'y'=>0,'z'=>0,'orbit_radius'=>0,'orbit_angle'=>0,'orbit_speed'=>0,'orbit_amplitude'=>0,'phase'=>0,'scale'=>1,
            ], false); ?>
        </script>
        <?php
    }

    private static function render_item($i, array $item, bool $numbered = true): void {
        $p = 'wya_menu_settings[items][' . $i . ']';
        ?>
        <div class="wya-item" data-index="<?php echo esc_attr($i); ?>">
            <div class="wya-item__head"><strong>Пункт <span class="wya-item-number"><?php echo $numbered ? (int)$i + 1 : '__NUMBER__'; ?></span></strong><button type="button" class="button-link-delete wya-remove-item">Удалить</button></div>
            <div class="wya-grid">
                <label>Название<?php echo self::field($p.'[title]', $item['title']); ?></label>
                <label class="wya-wide">URL / ссылка<?php echo self::field($p.'[url]', $item['url'], 'url', ['placeholder'=>'/creative-lab/ или https://example.com']); ?></label>
                <label>Описание<?php echo self::field($p.'[description]', $item['description']); ?></label>
                <label>Иконка<?php echo self::field($p.'[icon]', $item['icon'], 'url'); ?><button type="button" class="button wya-media-pick" data-target="<?php echo esc_attr($p.'[icon]'); ?>">Медиа</button></label>
                <label>Preview<?php echo self::field($p.'[preview]', $item['preview'], 'url'); ?><button type="button" class="button wya-media-pick" data-target="<?php echo esc_attr($p.'[preview]'); ?>">Медиа</button></label>
            </div>
            <details>
                <summary>Положение и полёт</summary>
                <div class="wya-grid wya-motion">
                    <label>X<?php echo self::field($p.'[x]', $item['x'], 'number', ['min'=>-50,'max'=>50,'step'=>'0.1']); ?> %</label>
                    <label>Y<?php echo self::field($p.'[y]', $item['y'], 'number', ['min'=>-50,'max'=>50,'step'=>'0.1']); ?> %</label>
                    <label>Z<?php echo self::field($p.'[z]', $item['z'], 'number', ['min'=>-300,'max'=>300,'step'=>'1']); ?> px</label>
                    <label>Радиус<?php echo self::field($p.'[orbit_radius]', $item['orbit_radius'], 'number', ['min'=>0,'max'=>50,'step'=>'0.1']); ?> %</label>
                    <label>Угол<?php echo self::field($p.'[orbit_angle]', $item['orbit_angle'], 'number', ['min'=>-360,'max'=>360,'step'=>'1']); ?> °</label>
                    <label>Скорость<?php echo self::field($p.'[orbit_speed]', $item['orbit_speed'], 'number', ['min'=>0,'max'=>2,'step'=>'0.01']); ?> обор./сек.</label>
                    <label>Амплитуда<?php echo self::field($p.'[orbit_amplitude]', $item['orbit_amplitude'], 'number', ['min'=>0,'max'=>50,'step'=>'0.1']); ?> %</label>
                    <label>Фаза<?php echo self::field($p.'[phase]', $item['phase'], 'number', ['min'=>-360,'max'=>360,'step'=>'1']); ?> °</label>
                    <label>Масштаб<?php echo self::field($p.'[scale]', $item['scale'], 'number', ['min'=>0.5,'max'=>3,'step'=>'0.05']); ?> ×</label>
                </div>
            </details>
            <div class="wya-checks">
                <label><input type="checkbox" name="<?php echo esc_attr($p.'[external]'); ?>" value="1" <?php checked(!empty($item['external'])); ?>> Внешняя ссылка</label>
                <label><input type="checkbox" name="<?php echo esc_attr($p.'[new_tab]'); ?>" value="1" <?php checked(!empty($item['new_tab'])); ?>> Новое окно</label>
                <label><input type="checkbox" name="<?php echo esc_attr($p.'[enabled]'); ?>" value="1" <?php checked(isset($item['enabled']) ? $item['enabled'] : 1); ?>> Активен</label>
            </div>
        </div>
        <?php
    }
}

WYA_Admin::init();
