<?php

declare(strict_types=1);

namespace CatalogMend\Application;

use CatalogMend\Support\HtmlTextProcessor;

final class ProductScanner
{
    private const FIELDS = ['post_title', 'post_excerpt', 'post_content'];

    public function __construct(private readonly HtmlTextProcessor $processor)
    {
    }

    public function scan(int $page = 1, int $perPage = 50): array
    {
        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => max(1, min(100, $perPage)),
            'paged' => max(1, $page),
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);

        $items = [];
        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $fields = [];
            foreach (self::FIELDS as $field) {
                $findings = $this->processor->detect((string) $post->{$field});
                if ($findings !== []) {
                    $fields[$field] = $findings;
                }
            }

            if ($fields !== []) {
                $items[] = [
                    'id' => $post->ID,
                    'title' => get_the_title($post),
                    'fields' => $fields,
                ];
            }
        }

        return [
            'items' => $items,
            'scanned' => count($query->posts),
            'page' => max(1, $page),
            'pages' => max(1, (int) $query->max_num_pages),
            'total' => (int) $query->found_posts,
        ];
    }

    public function preview(int $productId): array
    {
        $post = get_post($productId);
        if (! $post instanceof \WP_Post || $post->post_type !== 'product') {
            return [];
        }

        $preview = [];
        foreach (self::FIELDS as $field) {
            $before = (string) $post->{$field};
            $after = $this->processor->clean($before);
            $preview[$field] = [
                'before' => $before,
                'after' => $after,
                'changed' => $before !== $after,
            ];
        }

        return $preview;
    }
}
