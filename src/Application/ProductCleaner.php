<?php

declare(strict_types=1);

namespace CatalogMend\Application;

use CatalogMend\Support\HtmlTextProcessor;

final class ProductCleaner
{
    private const FIELDS = ['post_title', 'post_excerpt', 'post_content'];

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

        foreach (self::FIELDS as $field) {
            $before = (string) $post->{$field};
            $after = $this->processor->clean($before);

            if ($before !== $after) {
                $update[$field] = $after;
                $changed[] = $field;
            }
        }

        if ($changed === []) {
            return ['updated' => false, 'changed_fields' => [], 'error' => null];
        }

        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) {
            return ['updated' => false, 'changed_fields' => [], 'error' => $result->get_error_message()];
        }

        return ['updated' => true, 'changed_fields' => $changed, 'error' => null];
    }
}
