<?php

declare(strict_types=1);

namespace CatalogMend\Application;

use CatalogMend\Support\HtmlTextProcessor;

final class ProductCleaner
{
    private const FIELDS = ['post_title', 'post_excerpt', 'post_content'];
    private const BACKUP_META = '_catalogmend_ai_last_backup';

    public function __construct(private readonly HtmlTextProcessor $processor)
    {
    }

    public function clean(int $productId): array
    {
        $post = get_post($productId);
        if (! $post instanceof \WP_Post || $post->post_type !== 'product') {
            return ['updated' => false, 'changed_fields' => [], 'error' => 'invalid_product'];
        }

        $update = ['ID' => $productId];
        $changed = [];
        $backupFields = [];

        foreach (self::FIELDS as $field) {
            $before = (string) $post->{$field};
            $after = $this->processor->clean($before);

            if ($before !== $after) {
                $update[$field] = $after;
                $backupFields[$field] = $before;
                $changed[] = $field;
            }
        }

        if ($changed === []) {
            return ['updated' => false, 'changed_fields' => [], 'error' => null];
        }

        update_post_meta($productId, self::BACKUP_META, [
            'version' => 1,
            'created_at' => current_time('mysql', true),
            'fields' => $backupFields,
        ]);

        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) {
            return ['updated' => false, 'changed_fields' => [], 'error' => $result->get_error_message()];
        }

        return ['updated' => true, 'changed_fields' => $changed, 'error' => null];
    }

    public function canRollback(int $productId): bool
    {
        $backup = get_post_meta($productId, self::BACKUP_META, true);
        return is_array($backup) && ! empty($backup['fields']) && is_array($backup['fields']);
    }

    public function rollback(int $productId): array
    {
        $post = get_post($productId);
        $backup = get_post_meta($productId, self::BACKUP_META, true);

        if (! $post instanceof \WP_Post || $post->post_type !== 'product') {
            return ['updated' => false, 'error' => 'invalid_product'];
        }

        if (! is_array($backup) || empty($backup['fields']) || ! is_array($backup['fields'])) {
            return ['updated' => false, 'error' => 'backup_not_found'];
        }

        $update = ['ID' => $productId];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $backup['fields']) && is_string($backup['fields'][$field])) {
                $update[$field] = $backup['fields'][$field];
            }
        }

        if (count($update) === 1) {
            return ['updated' => false, 'error' => 'backup_empty'];
        }

        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) {
            return ['updated' => false, 'error' => $result->get_error_message()];
        }

        delete_post_meta($productId, self::BACKUP_META);
        return ['updated' => true, 'error' => null];
    }
}
