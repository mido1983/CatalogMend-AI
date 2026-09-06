<?php

declare(strict_types=1);

namespace CatalogMend\Infrastructure\Persistence;

final class AuditRepository
{
    private function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'catalogmend_audit';
    }

    public function record(
        int $productId,
        string $operation,
        array $changedFields,
        array $before,
        array $after,
        string $batchId = ''
    ): int {
        global $wpdb;

        $wpdb->insert(
            $this->table(),
            [
                'product_id' => $productId,
                'operation' => $operation,
                'changed_fields' => wp_json_encode(array_values($changedFields), JSON_UNESCAPED_UNICODE),
                'before_values' => wp_json_encode($before, JSON_UNESCAPED_UNICODE),
                'after_values' => wp_json_encode($after, JSON_UNESCAPED_UNICODE),
                'user_id' => get_current_user_id(),
                'batch_id' => $batchId,
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM ' . $this->table() . ' WHERE id = %d', $id),
            ARRAY_A
        );

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function recent(int $page = 1, int $perPage = 30): array
    {
        global $wpdb;

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $table = $this->table();

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $perPage, $offset),
            ARRAY_A
        );

        return [
            'items' => array_map(fn(array $row): array => $this->hydrate($row), is_array($rows) ? $rows : []),
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ];
    }

    public function exportRows(int $limit = 5000): array
    {
        global $wpdb;
        $limit = max(1, min(50000, $limit));
        $rows = $wpdb->get_results(
            $wpdb->prepare('SELECT * FROM ' . $this->table() . ' ORDER BY id DESC LIMIT %d', $limit),
            ARRAY_A
        );

        return array_map(fn(array $row): array => $this->hydrate($row), is_array($rows) ? $rows : []);
    }

    private function hydrate(array $row): array
    {
        foreach (['changed_fields', 'before_values', 'after_values'] as $key) {
            $decoded = json_decode((string) ($row[$key] ?? ''), true);
            $row[$key] = is_array($decoded) ? $decoded : [];
        }

        $row['id'] = (int) ($row['id'] ?? 0);
        $row['product_id'] = (int) ($row['product_id'] ?? 0);
        $row['user_id'] = (int) ($row['user_id'] ?? 0);

        return $row;
    }
}
