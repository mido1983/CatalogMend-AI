<?php
/**
 * Plugin Name: CatalogMend AI
 * Plugin URI: https://github.com/mido1983/CatalogMend-AI
 * Description: Detects and safely removes corrupted text fragments from WooCommerce product content without AI.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: CatalogMend AI
 * License: GPL-2.0-or-later
 * Text Domain: catalogmend-ai
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('CATALOGMEND_AI_VERSION', '0.1.0');
define('CATALOGMEND_AI_FILE', __FILE__);
define('CATALOGMEND_AI_PATH', plugin_dir_path(__FILE__));
define('CATALOGMEND_AI_URL', plugin_dir_url(__FILE__));

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

add_action('plugins_loaded', static function (): void {
    CatalogMend\Plugin::instance()->boot();
});
