<?php
defined('ABSPATH') || exit;

final class WYA_Admin {
    private const OPTION = 'wya_menu_settings';

    public static function defaults(): array {
        return [
            'aria_label' => 'WalkingYog animated menu',
            'media' => ['url'=>'','id'=>0,'type'=>'gif','pivot_x'=>50,'pivot_y'=>50],
            'mount' => ['target_selector'=>'.cover','replace_target'=>1],
            'scene' => ['head_desktop'=>320,'head_mobile'=>230,'stage_desktop'=>820,'stage_mobile'=>680],
            'layout' => [
                'mode'=>'side-balance','radius_desktop'=>350,'radius_mobile'=>205,
                'ellipse_x'=>1.12,'ellipse_y'=>0.72,'start_angle'=>0,'arc_range'=>120,
                'ring_gap'=>135,'item_size'=>58,'label_size'=>13,'label_width'=>140
            ],
            'lookat' => ['max_yaw'=>10,'max_pitch'=>6,'smoothing'=>0.14],
            'preview' => ['enabled'=>1,'width'=>210],
            'items' => []
        ];
    }

    public static function get_settings(): array {
        $saved = get_option(self::OPTION, []);
        return array_replace_recursive(self::defaults(), is_array($saved) ? $saved : []);
    }

    public static function init(): void {
        add_action('admin_menu', [self::class,'menu']);
        add_action('admin_init', [self::class,'settings']);
    }

    public static function menu(): void {
        add_options_page('WalkingYog Animated Menu','WalkingYog Animated Menu','manage_options','walkingyog-animated-menu',[self::class,'page']);
    }

    public static function settings(): void {
        register_setting('wya_menu', self::OPTION, [
            'type'=>'array',
            'sanitize_callback'=>[self::class,'sanitize']
        ]);
    }

    public static function sanitize($input): array {
        $cur = self::get_settings();
        $input = is_array($input) ? $input : [];
        $out = array_replace_recursive($cur, []);

        $out['aria_label'] = sanitize_text_field($input['aria_label'] ?? $cur['aria_label']);
        $out['media']['url'] = esc_url_raw($input['media']['url'] ?? '');
        $out['media']['id'] = absint($input['media']['id'] ?? 0);
        $type = strtolower((string)($input['media']['type'] ?? 'gif'));
        $out['media']['type'] = in_array($type,['gif','png','jpg','jpeg','webp','mp4'],true) ? $type : 'gif';
        $out['media']['pivot_x'] = max(0,min(100,(float)($input['media']['pivot_x'] ?? 50)));
        $out['media']['pivot_y'] = max(0,min(100,(float)($input['media']['pivot_y'] ?? 50)));

        $selector = sanitize_text_field($input['mount']['target_selector'] ?? '.cover');
        $out['mount']['target_selector'] = preg_match('/^[A-Za-z0-9_.#\-\s>\[\]="\':]+$/',$selector) ? $selector : '.cover';
        $out['mount']['replace_target'] = !empty($input['mount']['replace_target']) ? 1 : 0;

        $out['scene']['head_desktop'] = max(180,min(520,(float)($input['scene']['head_desktop'] ?? 320)));
        $out['scene']['head_mobile'] = max(140,min(340,(float)($input['scene']['head_mobile'] ?? 230)));
        $out['scene']['stage_desktop'] = max(700,min(1600,(float)($input['scene']['stage_desktop'] ?? 820)));
        $out['scene']['stage_mobile'] = max(520,min(900,(float)($input['scene']['stage_mobile'] ?? 680)));

        $mode = $input['layout']['mode'] ?? 'side-balance';
        $out['layout']['mode'] = in_array($mode,['side-balance','full-ring'],true) ? $mode : 'side-balance';
        $out['layout']['radius_desktop'] = max(220,min(700,(float)($input['layout']['radius_desktop'] ?? 350)));
        $out['layout']['radius_mobile'] = max(150,min(360,(float)($input['layout']['radius_mobile'] ?? 205)));
        $out['layout']['ellipse_x'] = max(0.8,min(1.7,(float)($input['layout']['ellipse_x'] ?? 1.12)));
        $out['layout']['ellipse_y'] = max(0.4,min(1.1,(float)($input['layout']['ellipse_y'] ?? 0.72)));
        $out['layout']['start_angle'] = fmod((float)($input['layout']['start_angle'] ?? 0),360);
        if ($out['layout']['start_angle'] < 0) $out['layout']['start_angle'] += 360;
        $out['layout']['arc_range'] = max(60,min(180,(float)($input['layout']['arc_range'] ?? 120)));
        $out['layout']['ring_gap'] = max(90,min(240,(float)($input['layout']['ring_gap'] ?? 135)));
        $out['layout']['item_size'] = max(44,min(92,(float)($input['layout']['item_size'] ?? 58)));
        $out['layout']['label_size'] = max(10,min(22,(float)($input['layout']['label_size'] ?? 13)));
        $out['layout']['label_width'] = max(90,min(240,(float)($input['layout']['label_width'] ?? 140)));

        $out['lookat']['max_yaw'] = max(0,min(18,(float)($input['lookat']['max_yaw'] ?? 10)));
        $out['lookat']['max_pitch'] = max(0,min(12,(float)($input['lookat']['max_pitch'] ?? 6)));
        $out['lookat']['smoothing'] = max(0.05,min(0.4,(float)($input['lookat']['smoothing'] ?? 0.14)));
        $out['preview']['enabled'] = !empty($input['preview']['enabled']) ? 1 : 0;
        $out['preview']['width'] = max(140,min(360,(float)($input['preview']['width'] ?? 210)));

        $out['items'] = [];
        foreach (is_array($input['items'] ?? null) ? $input['items'] : [] as $index=>$item) {
            $url = esc_url_raw($item['url'] ?? '');
            $title = sanitize_text_field($item['title'] ?? '');
            if ($url === '' || $title === '') continue;
            $out['items'][] = [
                'title'=>$title,'url'=>$url,
                'description'=>sanitize_text_field($item['description'] ?? ''),
                'icon'=>esc_url_raw($item['icon'] ?? ''),
                'preview'=>esc_url_raw($item['preview'] ?? ''),
                'external'=>!empty($item['external'])?1:0,
                'new_tab'=>!empty($item['new_tab'])?1:0,
                'enabled'=>!isset($item['enabled']) || !empty($item['enabled'])?1:0,
                'ring'=>max(0,min(3,(int)($item['ring'] ?? 0))),
                'order'=>max(0,min(999,(int)($item['order'] ?? $index)))
            ];
        }
        return $out;
    }

    private static function field(string $name,$value,string $type='text',array $attrs=[]): string {
        $a = '';
        foreach ($attrs as $k=>$v) $a .= ' '.esc_attr($k).'="'.esc_attr($v).'"';
        return '<input type="'.esc_attr($type).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'"'.$a.'>';
    }

    public static function page(): void {
        if (!current_user_can('manage_options')) return;
        $s = self::get_settings();
        ?>
        <div class="wrap wya-admin">
            <h1>WalkingYog Animated Menu 0.4.0</h1>
            <p class="notice-inline"><strong>Visual Pivot:</strong> выбирай точку прямо на GIF/PNG/MP4. Числа ниже только показывают сохранённые проценты.</p>

            <form method="post" action="options.php">
                <?php settings_fields('wya_menu'); ?>

                <section class="wya-panel">
                    <h2>1. Центральная голова</h2>
                    <p>
                        <button type="button" class="button button-primary wya-media-pick wya-pick-main">Выбрать GIF / PNG / JPG / MP4</button>
                        <input type="hidden" class="wya-media-id" name="wya_menu_settings[media][id]" value="<?php echo esc_attr($s['media']['id']); ?>">
                        <input type="hidden" class="wya-media-url" name="wya_menu_settings[media][url]" value="<?php echo esc_attr($s['media']['url']); ?>">
                        <input type="hidden" class="wya-media-type" name="wya_menu_settings[media][type]" value="<?php echo esc_attr($s['media']['type']); ?>">
                        <span class="wya-media-current"><?php echo $s['media']['url'] ? esc_html(wp_basename($s['media']['url'])) : 'Медиа не выбрано'; ?></span>
                    </p>

                    <div class="wya-focal-editor<?php echo $s['media']['url'] ? '' : ' is-empty'; ?>" data-url="<?php echo esc_attr($s['media']['url']); ?>" data-type="<?php echo esc_attr($s['media']['type']); ?>">
                        <div class="wya-focal-editor__toolbar">
                            <strong>Pivot / точка взгляда</strong>
                            <span>Кликни или перетащи маркер по самому изображению.</span>
                            <span class="wya-focal-values">X <b class="wya-pivot-x-value"><?php echo esc_html((float)$s['media']['pivot_x']); ?></b>% · Y <b class="wya-pivot-y-value"><?php echo esc_html((float)$s['media']['pivot_y']); ?></b>%</span>
                        </div>
                        <div class="wya-focal-stage">
                            <div class="wya-focal-media"></div>
                            <button type="button" class="wya-focal-marker" aria-label="Точка Pivot"></button>
                            <div class="wya-focal-crosshair" aria-hidden="true"></div>
                            <div class="wya-focal-empty">Сначала выбери медиа</div>
                        </div>
                        <input type="hidden" class="wya-pivot-x" name="wya_menu_settings[media][pivot_x]" value="<?php echo esc_attr($s['media']['pivot_x']); ?>">
                        <input type="hidden" class="wya-pivot-y" name="wya_menu_settings[media][pivot_y]" value="<?php echo esc_attr($s['media']['pivot_y']); ?>">
                    </div>
                    <p class="description">Для головы точка обычно ставится между глазами. Она фиксированная и не является движущейся частью GIF/MP4.</p>
                </section>

                <section class="wya-panel">
                    <h2>2. Замена старого .cover</h2>
                    <table class="form-table">
                        <tr><th>Контейнер</th><td><?php echo self::field('wya_menu_settings[mount][target_selector]',$s['mount']['target_selector'],'text',['class'=>'regular-text','placeholder'=>'.cover']); ?></td></tr>
                        <tr><th>Заменять background</th><td><label><input type="checkbox" name="wya_menu_settings[mount][replace_target]" value="1" <?php checked($s['mount']['replace_target']); ?>> Да — старый head.gif не должен оставаться второй головой.</label></td></tr>
                    </table>
                </section>

                <section class="wya-panel">
                    <h2>3. Пространство и radial/orbit</h2>
                    <table class="form-table">
                        <tr><th>Раскладка</th><td><select name="wya_menu_settings[layout][mode]"><option value="side-balance" <?php selected($s['layout']['mode'],'side-balance'); ?>>Боковые дуги</option><option value="full-ring" <?php selected($s['layout']['mode'],'full-ring'); ?>>Полное кольцо</option></select><p class="description">Боковые дуги оставляют верхнюю середину над головой свободной.</p></td></tr>
                        <tr><th>Размер головы desktop</th><td><?php echo self::field('wya_menu_settings[scene][head_desktop]',$s['scene']['head_desktop'],'number',['min'=>180,'max'=>520,'step'=>5]); ?> px</td></tr>
                        <tr><th>Размер головы mobile</th><td><?php echo self::field('wya_menu_settings[scene][head_mobile]',$s['scene']['head_mobile'],'number',['min'=>140,'max'=>340,'step'=>5]); ?> px</td></tr>
                        <tr><th>Высота сцены desktop</th><td><?php echo self::field('wya_menu_settings[scene][stage_desktop]',$s['scene']['stage_desktop'],'number',['min'=>700,'max'=>1600,'step'=>10]); ?> px</td></tr>
                        <tr><th>Высота сцены mobile</th><td><?php echo self::field('wya_menu_settings[scene][stage_mobile]',$s['scene']['stage_mobile'],'number',['min'=>520,'max'=>900,'step'=>10]); ?> px</td></tr>
                        <tr><th>Радиус desktop</th><td><?php echo self::field('wya_menu_settings[layout][radius_desktop]',$s['layout']['radius_desktop'],'number',['min'=>220,'max'=>700,'step'=>5]); ?> px</td></tr>
                        <tr><th>Радиус mobile</th><td><?php echo self::field('wya_menu_settings[layout][radius_mobile]',$s['layout']['radius_mobile'],'number',['min'=>150,'max'=>360,'step'=>5]); ?> px</td></tr>
                        <tr><th>Эллипс X</th><td><?php echo self::field('wya_menu_settings[layout][ellipse_x]',$s['layout']['ellipse_x'],'number',['min'=>0.8,'max'=>1.7,'step'=>0.01]); ?> ×</td></tr>
                        <tr><th>Эллипс Y</th><td><?php echo self::field('wya_menu_settings[layout][ellipse_y]',$s['layout']['ellipse_y'],'number',['min'=>0.4,'max'=>1.1,'step'=>0.01]); ?> ×</td></tr>
                        <tr><th>Начальный угол</th><td><?php echo self::field('wya_menu_settings[layout][start_angle]',$s['layout']['start_angle'],'number',['min'=>0,'max'=>359,'step'=>1]); ?> °</td></tr>
                        <tr><th>Разлёт дуги</th><td><?php echo self::field('wya_menu_settings[layout][arc_range]',$s['layout']['arc_range'],'number',['min'=>60,'max'=>180,'step'=>1]); ?> °</td></tr>
                        <tr><th>Расстояние колец</th><td><?php echo self::field('wya_menu_settings[layout][ring_gap]',$s['layout']['ring_gap'],'number',['min'=>90,'max'=>240,'step'=>5]); ?> px</td></tr>
                        <tr><th>Размер узла</th><td><?php echo self::field('wya_menu_settings[layout][item_size]',$s['layout']['item_size'],'number',['min'=>44,'max'=>92,'step'=>2]); ?> px</td></tr>
                        <tr><th>Размер подписи</th><td><?php echo self::field('wya_menu_settings[layout][label_size]',$s['layout']['label_size'],'number',['min'=>10,'max'=>22,'step'=>1]); ?> px</td></tr>
                        <tr><th>Ширина подписи</th><td><?php echo self::field('wya_menu_settings[layout][label_width]',$s['layout']['label_width'],'number',['min'=>90,'max'=>240,'step'=>5]); ?> px</td></tr>
                    </table>
                    <p class="description">Ring только выбирает расстояние от центра. X/Y для каждого пункта больше не нужны: положение получается из количества пунктов и угловой раскладки.</p>
                </section>

                <section class="wya-panel">
                    <h2>4. Поворот головы</h2>
                    <table class="form-table">
                        <tr><th>Yaw</th><td><?php echo self::field('wya_menu_settings[lookat][max_yaw]',$s['lookat']['max_yaw'],'number',['min'=>0,'max'=>18,'step'=>0.5]); ?> °</td></tr>
                        <tr><th>Pitch</th><td><?php echo self::field('wya_menu_settings[lookat][max_pitch]',$s['lookat']['max_pitch'],'number',['min'=>0,'max'=>12,'step'=>0.5]); ?> °</td></tr>
                        <tr><th>Плавность</th><td><?php echo self::field('wya_menu_settings[lookat][smoothing]',$s['lookat']['smoothing'],'number',['min'=>0.05,'max'=>0.4,'step'=>0.01]); ?></td></tr>
                    </table>
                </section>

                <section class="wya-panel">
                    <h2>5. Preview выбранного пункта</h2>
                    <table class="form-table">
                        <tr><th>Включить</th><td><label><input type="checkbox" name="wya_menu_settings[preview][enabled]" value="1" <?php checked($s['preview']['enabled']); ?>> Показывать GIF/MP4 preview рядом с выбранным пунктом.</label></td></tr>
                        <tr><th>Ширина</th><td><?php echo self::field('wya_menu_settings[preview][width]',$s['preview']['width'],'number',['min'=>140,'max'=>360,'step'=>10]); ?> px</td></tr>
                    </table>
                </section>

                <section class="wya-panel">
                    <h2>6. Пункты меню</h2>
                    <p>Каждый пункт остаётся настоящим <code>&lt;a href&gt;</code>. На desktop выбирается только фактически наведённый пункт. На touch первый тап фокусирует, второй открывает.</p>
                    <div id="wya-items" class="wya-items">
                        <?php foreach ($s['items'] as $i=>$item): self::render_item($i,$item); endforeach; ?>
                    </div>
                    <p><button type="button" class="button button-secondary" id="wya-add-item">+ Добавить пункт</button></p>
                </section>

                <?php submit_button('Сохранить меню'); ?>
            </form>
        </div>

        <script type="text/html" id="tmpl-wya-item">
            <?php self::render_item('__INDEX__',[
                'title'=>'','url'=>'','description'=>'','icon'=>'','preview'=>'',
                'external'=>0,'new_tab'=>0,'enabled'=>1,'ring'=>0,'order'=>0
            ],false); ?>
        </script>
        <?php
    }

    private static function render_item($i,array $item,bool $numbered=true): void {
        $p = 'wya_menu_settings[items]['.$i.']';
        ?>
        <div class="wya-item" data-index="<?php echo esc_attr($i); ?>">
            <div class="wya-item__head">
                <strong>Пункт <span class="wya-item-number"><?php echo $numbered ? (int)$i+1 : '__NUMBER__'; ?></span></strong>
                <button type="button" class="button-link-delete wya-remove-item">Удалить</button>
            </div>
            <div class="wya-grid">
                <label>Название<?php echo self::field($p.'[title]',$item['title']); ?></label>
                <label class="wya-wide">URL<?php echo self::field($p.'[url]',$item['url'],'url',['placeholder'=>'/creative-lab/ или https://example.com']); ?></label>
                <label>Описание<?php echo self::field($p.'[description]',$item['description']); ?></label>
                <label>Ring<?php echo self::field($p.'[ring]',$item['ring'],'number',['min'=>0,'max'=>3,'step'=>1]); ?></label>
                <label>Порядок<?php echo self::field($p.'[order]',$item['order'],'number',['min'=>0,'max'=>999,'step'=>1]); ?></label>
                <label>Иконка<?php echo self::field($p.'[icon]',$item['icon'],'url'); ?><button type="button" class="button wya-media-pick" data-target="<?php echo esc_attr($p.'[icon]'); ?>">Медиа</button></label>
                <label>Preview<?php echo self::field($p.'[preview]',$item['preview'],'url'); ?><button type="button" class="button wya-media-pick" data-target="<?php echo esc_attr($p.'[preview]'); ?>">Медиа</button></label>
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
