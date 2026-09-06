<?php
/**
 * Plugin Name: CatalogMend AI
 * Plugin URI: https://github.com/mido1983/CatalogMend-AI
 * Description: Detects and safely removes corrupted text fragments from WooCommerce product content without AI.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 10.2
 * Author: CatalogMend AI
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: catalogmend-ai
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('CATALOGMEND_AI_VERSION', '1.0.0');
define('CATALOGMEND_AI_FILE', __FILE__);
define('CATALOGMEND_AI_PATH', plugin_dir_path(__FILE__));
define('CATALOGMEND_AI_URL', plugin_dir_url(__FILE__));

add_filter('plugin_action_links_' . plugin_basename(__FILE__), static function (array $links): array {
    $settingsUrl = add_query_arg(
        [
            'page' => 'catalogmend-ai',
            'tab'  => 'settings',
        ],
        admin_url('admin.php')
    );

    array_unshift(
        $links,
        '<a href="' . esc_url($settingsUrl) . '">' . esc_html__('Settings', 'catalogmend-ai') . '</a>'
    );

    return $links;
});

spl_autoload_register(static function (string $class): void {
    $prefix = 'CatalogMend\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = CATALOGMEND_AI_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, ['CatalogMend\\Infrastructure\\WordPress\\Installer', 'activate']);
register_deactivation_hook(__FILE__, ['CatalogMend\\Infrastructure\\WordPress\\Installer', 'deactivate']);

add_action('before_woocommerce_init', static function (): void {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('CatalogMend AI requires WooCommerce to be installed and active.', 'catalogmend-ai') . '</p></div>';
            }
        });
        return;
    }

    CatalogMend\Plugin::instance()->boot();
});
