<?php

declare(strict_types=1);

namespace CatalogMend\Application;

use CatalogMend\Infrastructure\Persistence\AuditRepository;
use CatalogMend\Support\HtmlTextProcessor;

final class ProductCleaner
{
    private const FIELDS = ['post_title', 'post_excerpt', 'post_content'];

    public function __construct(
        private readonly HtmlTextProcessor $processor,
        private readonly AuditRepository $audit
    ) {
    }

    public function clean(int $productId, string $batchId = ''): array
    {
        $post = get_post($productId);
        if (! $post instanceof \WP_Post || $post->post_type !== 'product') {
            return ['updated' => false, 'changed_fields' => [], 'audit_id' => 0, 'error' => 'invalid_product'];
        }

        $update = ['ID' => $productId];
        $before = [];
        $after = [];
        $changed = [];

        foreach (self::FIELDS as $field) {
            $source = (string) $post->{$field};
            $cleaned = $this->processor->clean($source);

            if ($source === $cleaned) {
                continue;
            }

            if (! $this->isValidUtf8($cleaned)) {
                return ['updated' => false, 'changed_fields' => [], 'audit_id' => 0, 'error' => 'invalid_utf8_after_clean'];
            }

            $update[$field] = $cleaned;
            $before[$field] = $source;
            $after[$field] = $cleaned;
            $changed[] = $field;
        }

        if ($changed === []) {
            return ['updated' => false, 'changed_fields' => [], 'audit_id' => 0, 'error' => null];
        }

        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) {
            return ['updated' => false, 'changed_fields' => [], 'audit_id' => 0, 'error' => $result->get_error_message()];
        }

        $auditId = $this->audit->record($productId, 'clean', $changed, $before, $after, $batchId);

        return ['updated' => true, 'changed_fields' => $changed, 'audit_id' => $auditId, 'error' => null];
    }

    public function rollback(int $auditId): array
    {
        $event = $this->audit->find($auditId);
        if ($event === null || ($event['operation'] ?? '') !== 'clean') {
            return ['updated' => false, 'error' => 'invalid_audit_event'];
        }

        $productId = (int) $event['product_id'];
        $post = get_post($productId);
        if (! $post instanceof \WP_Post || $post->post_type !== 'product') {
            return ['updated' => false, 'error' => 'invalid_product'];
        }

        $beforeValues = is_array($event['before_values']) ? $event['before_values'] : [];
        $changedFields = is_array($event['changed_fields']) ? $event['changed_fields'] : [];
        $update = ['ID' => $productId];
        $current = [];
        $restored = [];

        foreach ($changedFields as $field) {
            if (! in_array($field, self::FIELDS, true) || ! array_key_exists($field, $beforeValues)) {
                continue;
            }

            $current[$field] = (string) $post->{$field};
            $restored[$field] = (string) $beforeValues[$field];
            $update[$field] = (string) $beforeValues[$field];
        }

        if (count($update) === 1) {
            return ['updated' => false, 'error' => 'nothing_to_restore'];
        }

        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) {
            return ['updated' => false, 'error' => $result->get_error_message()];
        }

        $this->audit->record($productId, 'rollback', array_keys($restored), $current, $restored, (string) ($event['batch_id'] ?? ''));

        return ['updated' => true, 'error' => null];
    }

    private function isValidUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }
}
