<?php
/**
 * Plugin Name: WalkingYog Animated Space Menu
 * Description: Spatial animated WordPress menu with a central GIF/MP4 object, calibrated look-at pivot and automatic radial navigation.
 * Version: 0.3.0
 * Author: WalkingYog
 * Text Domain: walkingyog-animated-menu
 */

defined('ABSPATH') || exit;

define('WYA_MENU_VERSION', '0.3.0');
define('WYA_MENU_FILE', __FILE__);
define('WYA_MENU_URL', plugin_dir_url(__FILE__));
define('WYA_MENU_PATH', plugin_dir_path(__FILE__));

require_once WYA_MENU_PATH . 'admin/class-wya-admin.php';

final class WalkingYog_Animated_Menu {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_shortcode('wyg_animated_menu', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    }

    public function register_assets(): void {
        wp_register_style('wya-menu', WYA_MENU_URL . 'assets/css/animated-menu.css', [], WYA_MENU_VERSION);
        wp_register_script('wya-menu', WYA_MENU_URL . 'assets/js/animated-menu.js', [], WYA_MENU_VERSION, true);
    }

    public function admin_assets(string $hook): void {
        if ('settings_page_walkingyog-animated-menu' !== $hook) return;
        wp_enqueue_style('wya-menu-admin', WYA_MENU_URL . 'assets/css/admin.css', [], WYA_MENU_VERSION);
        wp_enqueue_media();
        wp_enqueue_script('wya-menu-admin', WYA_MENU_URL . 'assets/js/admin.js', ['jquery'], WYA_MENU_VERSION, true);
    }

    public function render_shortcode(array $atts = []): string {
        $settings = WYA_Admin::get_settings();
        $atts = shortcode_atts([
            'class' => '',
            'media' => '',
            'media_type' => '',
            'pivot_x' => '',
            'pivot_y' => '',
        ], $atts, 'wyg_animated_menu');

        if ($atts['media'] !== '') $settings['media']['url'] = esc_url_raw($atts['media']);
        if ($atts['media_type'] !== '') $settings['media']['type'] = sanitize_key($atts['media_type']);
        if ($atts['pivot_x'] !== '') $settings['media']['pivot_x'] = max(0, min(100, (float)$atts['pivot_x']));
        if ($atts['pivot_y'] !== '') $settings['media']['pivot_y'] = max(0, min(100, (float)$atts['pivot_y']));

        if (empty($settings['media']['url']) || empty($settings['items'])) return '';

        wp_enqueue_style('wya-menu');
        wp_enqueue_script('wya-menu');

        $id = 'wya-menu-' . wp_rand(1000, 999999);
        $items = array_values(array_filter($settings['items'], static function ($item) {
            return !empty($item['enabled']) && !empty($item['url']) && !empty($item['title']);
        }));

        if (!$items) return '';

        $layout = $settings['layout'];
        ?>
        <nav
            id="<?php echo esc_attr($id); ?>"
            class="wya-menu wya-layout-<?php echo esc_attr($layout['mode']); ?> <?php echo esc_attr($atts['class']); ?>"
            aria-label="<?php echo esc_attr($settings['aria_label']); ?>"
            data-pivot-x="<?php echo esc_attr((float)$settings['media']['pivot_x']); ?>"
            data-pivot-y="<?php echo esc_attr((float)$settings['media']['pivot_y']); ?>"
            data-radius-desktop="<?php echo esc_attr((float)$layout['radius_desktop']); ?>"
            data-radius-mobile="<?php echo esc_attr((float)$layout['radius_mobile']); ?>"
            data-ellipse-y="<?php echo esc_attr((float)$layout['ellipse_y']); ?>"
            data-ring-gap="<?php echo esc_attr((float)$layout['ring_gap']); ?>"
            data-label-size="<?php echo esc_attr((float)$layout['label_size']); ?>"
            data-label-width="<?php echo esc_attr((float)$layout['label_width']); ?>"
            data-max-yaw="<?php echo esc_attr((float)$settings['lookat']['max_yaw']); ?>"
            data-max-pitch="<?php echo esc_attr((float)$settings['lookat']['max_pitch']); ?>"
            data-lookat-smoothing="<?php echo esc_attr((float)$settings['lookat']['smoothing']); ?>"
            data-mask="<?php echo esc_attr($settings['media']['mask']); ?>"
        >
            <div class="wya-menu__stage">
                <div class="wya-menu__media-wrap" aria-hidden="true">
                    <?php if ($settings['media']['type'] === 'mp4'): ?>
                        <video class="wya-menu__media" autoplay muted loop playsinline preload="metadata" src="<?php echo esc_url($settings['media']['url']); ?>"></video>
                    <?php else: ?>
                        <img class="wya-menu__media" src="<?php echo esc_url($settings['media']['url']); ?>" alt="">
                    <?php endif; ?>
                    <span class="wya-menu__focus-point" aria-hidden="true"></span>
                </div>

                <div class="wya-menu__items" role="list">
                    <?php foreach ($items as $index => $item):
                        $item_id = $id . '-item-' . $index;
                        $target = !empty($item['new_tab']) ? '_blank' : '_self';
                        $rel = !empty($item['new_tab']) ? 'noopener noreferrer' : '';
                        $preview = !empty($item['preview']) ? $item['preview'] : '';
                    ?>
                        <a
                            id="<?php echo esc_attr($item_id); ?>"
                            class="wya-menu__item"
                            role="listitem"
                            href="<?php echo esc_url($item['url']); ?>"
                            target="<?php echo esc_attr($target); ?>"
                            <?php if ($rel): ?>rel="<?php echo esc_attr($rel); ?>"<?php endif; ?>
                            data-ring="<?php echo esc_attr((int)($item['ring'] ?? 0)); ?>"
                            data-order="<?php echo esc_attr((int)($item['order'] ?? $index)); ?>"
                            data-preview="<?php echo esc_url($preview); ?>"
                            data-external="<?php echo !empty($item['external']) ? '1' : '0'; ?>"
                        >
                            <span class="wya-menu__item-glow" aria-hidden="true"></span>
                            <?php if (!empty($item['icon'])): ?>
                                <img class="wya-menu__item-icon" src="<?php echo esc_url($item['icon']); ?>" alt="" aria-hidden="true">
                            <?php endif; ?>
                            <span class="wya-menu__item-title"><?php echo esc_html($item['title']); ?></span>
                            <?php if (!empty($item['description'])): ?>
                                <span class="wya-menu__item-description"><?php echo esc_html($item['description']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <span class="wya-menu__sr-status" aria-live="polite"></span>
        </nav>
        <?php
        return (string) ob_get_clean();
    }
}

WalkingYog_Animated_Menu::instance();
