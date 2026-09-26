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
                'mask' => 'none',
            ],
            'layout' => [
                'mode' => 'auto-ring',
                'radius_desktop' => 300,
                'radius_mobile' => 175,
                'ellipse_y' => 0.72,
                'ring_gap' => 105,
                'label_size' => 14,
                'label_width' => 150,
            ],
            'lookat' => [
                'max_yaw' => 10,
                'max_pitch' => 6,
                'smoothing' => 0.16,
            ],
            'items' => [],
        ];
        return wp_parse_args(get_option(self::OPTION, []), $defaults);
    }

    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'settings']);
    }

    public static function menu(): void {
        add_options_page('WalkingYog Animated Menu', 'WalkingYog Animated Menu', 'manage_options', 'walkingyog-animated-menu', [self::class, 'page']);
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
        $out['media']['pivot_x'] = max(0, min(100, (float)($input['media']['pivot_x'] ?? 50)));
        $out['media']['pivot_y'] = max(0, min(100, (float)($input['media']['pivot_y'] ?? 50)));
        $out['media']['mask'] = in_array(($input['media']['mask'] ?? 'none'), ['none', 'round'], true) ? $input['media']['mask'] : 'none';

        $out['layout']['mode'] = in_array(($input['layout']['mode'] ?? 'auto-ring'), ['auto-ring', 'manual'], true) ? $input['layout']['mode'] : 'auto-ring';
        $out['layout']['radius_desktop'] = max(220, min(560, (float)($input['layout']['radius_desktop'] ?? 300)));
        $out['layout']['radius_mobile'] = max(130, min(300, (float)($input['layout']['radius_mobile'] ?? 175)));
        $out['layout']['ellipse_y'] = max(0.45, min(1, (float)($input['layout']['ellipse_y'] ?? 0.72)));
        $out['layout']['ring_gap'] = max(70, min(180, (float)($input['layout']['ring_gap'] ?? 105)));
        $out['layout']['label_size'] = max(10, min(24, (float)($input['layout']['label_size'] ?? 14)));
        $out['layout']['label_width'] = max(80, min(260, (float)($input['layout']['label_width'] ?? 150)));

        $out['lookat']['max_yaw'] = max(0, min(20, (float)($input['lookat']['max_yaw'] ?? 10)));
        $out['lookat']['max_pitch'] = max(0, min(12, (float)($input['lookat']['max_pitch'] ?? 6)));
        $out['lookat']['smoothing'] = max(0.05, min(0.5, (float)($input['lookat']['smoothing'] ?? 0.16)));

        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $out['items'] = [];
        foreach ($items as $index => $item) {
            $url = esc_url_raw($item['url'] ?? '');
            $title = sanitize_text_field($item['title'] ?? '');
            if ($url === '' || $title === '') continue;

            $out['items'][] = [
                'title' => $title,
                'url' => $url,
                'description' => sanitize_text_field($item['description'] ?? ''),
                'icon' => esc_url_raw($item['icon'] ?? ''),
                'preview' => esc_url_raw($item['preview'] ?? ''),
                'external' => !empty($item['external']) ? 1 : 0,
                'new_tab' => !empty($item['new_tab']) ? 1 : 0,
                'enabled' => !isset($item['enabled']) || !empty($item['enabled']) ? 1 : 0,
                'ring' => max(0, min(3, (int)($item['ring'] ?? 0))),
                'order' => max(0, min(999, (int)($item['order'] ?? $index))),
            ];
        }
        return $out;
    }

    private static function field(string $name, $value, string $type = 'text', array $attrs = []): string {
        $attr = '';
        foreach ($attrs as $key => $val) $attr .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
        return '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $attr . '>';
    }

    public static function page(): void {
        if (!current_user_can('manage_options')) return;
        $s = self::get_settings();
        ?>
        <div class="wrap wya-admin">
            <h1>WalkingYog Animated Menu 0.3.0</h1>
            <p class="notice-inline"><strong>Автоматическая раскладка</strong> теперь сама разводит пункты по орбите. Ручные X/Y больше не используются как основной способ позиционирования.</p>

            <form method="post" action="options.php">
                <?php settings_fields('wya_menu'); ?>

                <h2>Центральный объект</h2>
                <table class="form-table">
                    <tr><th>GIF / MP4</th><td>
                        <div class="wya-media-control">
                            <input class="regular-text wya-media-url" name="wya_menu_settings[media][url]" value="<?php echo esc_attr($s['media']['url']); ?>">
                            <button type="button" class="button wya-media-pick" data-target="wya_menu_settings[media][url]">Выбрать из медиатеки</button>
                        </div>
                    </td></tr>
                    <tr><th>Тип</th><td><select name="wya_menu_settings[media][type]"><option value="gif" <?php selected($s['media']['type'],'gif'); ?>>GIF</option><option value="mp4" <?php selected($s['media']['type'],'mp4'); ?>>MP4</option></select></td></tr>
                    <tr><th>Pivot X / Y</th><td>
                        X <?php echo self::field('wya_menu_settings[media][pivot_x]', $s['media']['pivot_x'], 'number', ['min'=>0,'max'=>100,'step'=>'0.1']); ?> %
                        &nbsp;&nbsp; Y <?php echo self::field('wya_menu_settings[media][pivot_y]', $s['media']['pivot_y'], 'number', ['min'=>0,'max'=>100,'step'=>'0.1']); ?> %
                        <p class="description">Это неподвижная калибровочная точка взгляда. Для головы обычно ставится между глазами.</p>
                    </td></tr>
                    <tr><th>Маска</th><td><select name="wya_menu_settings[media][mask]"><option value="none" <?php selected($s['media']['mask'],'none'); ?>>Без маски — сохранять форму/alpha исходника</option><option value="round" <?php selected($s['media']['mask'],'round'); ?>>Круглая маска</option></select></td></tr>
                </table>

                <h2>Радиальное меню</h2>
                <table class="form-table">
                    <tr><th>Раскладка</th><td><select name="wya_menu_settings[layout][mode]"><option value="auto-ring" <?php selected($s['layout']['mode'],'auto-ring'); ?>>Авто-орбита</option><option value="manual" <?php selected($s['layout']['mode'],'manual'); ?>>Ручная (для специальных сцен)</option></select></td></tr>
                    <tr><th>Радиус desktop</th><td><?php echo self::field('wya_menu_settings[layout][radius_desktop]', $s['layout']['radius_desktop'], 'number', ['min'=>220,'max'=>560,'step'=>'5']); ?> px</td></tr>
                    <tr><th>Радиус mobile</th><td><?php echo self::field('wya_menu_settings[layout][radius_mobile]', $s['layout']['radius_mobile'], 'number', ['min'=>130,'max'=>300,'step'=>'5']); ?> px</td></tr>
                    <tr><th>Вертикальный масштаб орбиты</th><td><?php echo self::field('wya_menu_settings[layout][ellipse_y]', $s['layout']['ellipse_y'], 'number', ['min'=>0.45,'max'=>1,'step'=>'0.01']); ?> × <span class="description">0.72 даёт широкую горизонтальную сцену.</span></td></tr>
                    <tr><th>Расстояние между кольцами</th><td><?php echo self::field('wya_menu_settings[layout][ring_gap]', $s['layout']['ring_gap'], 'number', ['min'=>70,'max'=>180,'step'=>'5']); ?> px</td></tr>
                    <tr><th>Размер текста</th><td><?php echo self::field('wya_menu_settings[layout][label_size]', $s['layout']['label_size'], 'number', ['min'=>10,'max'=>24,'step'=>'1']); ?> px</td></tr>
                    <tr><th>Макс. ширина подписи</th><td><?php echo self::field('wya_menu_settings[layout][label_width]', $s['layout']['label_width'], 'number', ['min'=>80,'max'=>260,'step'=>'5']); ?> px</td></tr>
                </table>

                <h2>Взгляд центрального объекта</h2>
                <table class="form-table">
                    <tr><th>Максимальный yaw</th><td><?php echo self::field('wya_menu_settings[lookat][max_yaw]', $s['lookat']['max_yaw'], 'number', ['min'=>0,'max'=>20,'step'=>'0.5']); ?> °</td></tr>
                    <tr><th>Максимальный pitch</th><td><?php echo self::field('wya_menu_settings[lookat][max_pitch]', $s['lookat']['max_pitch'], 'number', ['min'=>0,'max'=>12,'step'=>'0.5']); ?> °</td></tr>
                    <tr><th>Плавность</th><td><?php echo self::field('wya_menu_settings[lookat][smoothing]', $s['lookat']['smoothing'], 'number', ['min'=>0.05,'max'=>0.5,'step'=>'0.01']); ?></td></tr>
                </table>

                <h2>Пункты меню</h2>
                <p>Каждый пункт имеет собственную настоящую ссылку. В режиме «Авто-орбита» позиция рассчитывается автоматически; <strong>Ring 0</strong> — первое кольцо, Ring 1 — второе.</p>
                <div id="wya-items" class="wya-items">
                    <?php foreach ($s['items'] as $i => $item): self::render_item($i, $item); endforeach; ?>
                </div>
                <p><button type="button" class="button button-secondary" id="wya-add-item">+ Добавить пункт</button></p>

                <?php submit_button('Сохранить меню'); ?>
            </form>
        </div>
        <script type="text/html" id="tmpl-wya-item">
            <?php self::render_item('__INDEX__', ['title'=>'','url'=>'','description'=>'','icon'=>'','preview'=>'','external'=>0,'new_tab'=>0,'enabled'=>1,'ring'=>0,'order'=>0], false); ?>
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
                <label class="wya-wide">URL<?php echo self::field($p.'[url]', $item['url'], 'url', ['placeholder'=>'/creative-lab/ или https://example.com']); ?></label>
                <label>Описание<?php echo self::field($p.'[description]', $item['description']); ?></label>
                <label>Ring<?php echo self::field($p.'[ring]', $item['ring'], 'number', ['min'=>0,'max'=>3,'step'=>1]); ?></label>
                <label>Порядок<?php echo self::field($p.'[order]', $item['order'], 'number', ['min'=>0,'max'=>999,'step'=>1]); ?></label>
                <label>Иконка<?php echo self::field($p.'[icon]', $item['icon'], 'url'); ?><button type="button" class="button wya-media-pick" data-target="<?php echo esc_attr($p.'[icon]'); ?>">Медиа</button></label>
                <label>Preview<?php echo self::field($p.'[preview]', $item['preview'], 'url'); ?><button type="button" class="button wya-media-pick" data-target="<?php echo esc_attr($p.'[preview]'); ?>">Медиа</button></label>
            </div>
            <div class="wya-checks">
                <label><input type="checkbox" name="<?php echo esc_attr($p.'[external]'); ?>" value="1" <?php checked(!empty($item['external'])); ?>> Внешняя</label>
                <label><input type="checkbox" name="<?php echo esc_attr($p.'[new_tab]'); ?>" value="1" <?php checked(!empty($item['new_tab'])); ?>> Новое окно</label>
                <label><input type="checkbox" name="<?php echo esc_attr($p.'[enabled]'); ?>" value="1" <?php checked(isset($item['enabled']) ? $item['enabled'] : 1); ?>> Активен</label>
            </div>
        </div>
        <?php
    }
}
WYA_Admin::init();
