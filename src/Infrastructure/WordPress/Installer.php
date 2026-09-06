<?php

declare(strict_types=1);

namespace CatalogMend\Infrastructure\WordPress;

final class Installer
{
    public static function activate(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'catalogmend_audit';
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            operation VARCHAR(32) NOT NULL,
            changed_fields LONGTEXT NOT NULL,
            before_values LONGTEXT NOT NULL,
            after_values LONGTEXT NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            batch_id VARCHAR(64) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY batch_id (batch_id),
            KEY created_at (created_at)
        ) {$charset};");

        add_option('catalogmend_batch_size', 20, '', false);
        add_option('catalogmend_delete_data_on_uninstall', '0', '', false);
        update_option('catalogmend_db_version', '1');
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('catalogmend_process_batch');
        delete_option('catalogmend_batch_lock');
    }
}
