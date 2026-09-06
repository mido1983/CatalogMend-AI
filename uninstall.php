<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (get_option('catalogmend_delete_data_on_uninstall', '0') !== '1') {
    return;
}

global $wpdb;
$table = $wpdb->prefix . 'catalogmend_audit';
$wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

delete_option('catalogmend_batch_size');
delete_option('catalogmend_delete_data_on_uninstall');
delete_option('catalogmend_db_version');
delete_option('catalogmend_batch_job');
delete_option('catalogmend_batch_lock');
wp_clear_scheduled_hook('catalogmend_process_batch');
