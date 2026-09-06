<?php

declare(strict_types=1);

namespace CatalogMend\Application;

final class BatchJobService
{
    private const OPTION = 'catalogmend_batch_job';
    private const LOCK = 'catalogmend_batch_lock';
    private const HOOK = 'catalogmend_process_batch';
    private const LOCK_TTL = 600;

    public function __construct(private readonly ProductCleaner $cleaner)
    {
    }

    public function register(): void
    {
        add_action(self::HOOK, [$this, 'process']);
    }

    public function start(array $productIds): array
    {
        $active = $this->state();
        if (in_array((string) ($active['status'] ?? ''), ['queued', 'running'], true)) {
            return ['started' => false, 'error' => 'job_already_running', 'job' => $active];
        }

        $ids = array_values(array_unique(array_filter(array_map('absint', $productIds))));
        $ids = array_values(array_filter($ids, static function (int $id): bool {
            $post = get_post($id);
            return $post instanceof \WP_Post && $post->post_type === 'product';
        }));

        if ($ids === []) {
            return ['started' => false, 'error' => 'no_products', 'job' => []];
        }

        $job = [
            'id' => wp_generate_uuid4(),
            'status' => 'queued',
            'ids' => $ids,
            'cursor' => 0,
            'total' => count($ids),
            'processed' => 0,
            'changed' => 0,
            'failed' => 0,
            'user_id' => get_current_user_id(),
            'started_at' => current_time('mysql', true),
            'finished_at' => '',
            'last_error' => '',
        ];

        update_option(self::OPTION, $job, false);
        $this->schedule();

        return ['started' => true, 'error' => null, 'job' => $job];
    }

    public function process(): void
    {
        if (! $this->acquireLock()) {
            return;
        }

        try {
            $job = $this->state();
            if (! in_array((string) ($job['status'] ?? ''), ['queued', 'running'], true)) {
                return;
            }

            $job['status'] = 'running';
            $batchSize = max(1, min(100, (int) get_option('catalogmend_batch_size', 20)));
            $ids = is_array($job['ids'] ?? null) ? $job['ids'] : [];
            $cursor = max(0, (int) ($job['cursor'] ?? 0));
            $end = min(count($ids), $cursor + $batchSize);
            $userId = max(0, (int) ($job['user_id'] ?? 0));

            for ($i = $cursor; $i < $end; $i++) {
                $result = $this->cleaner->clean((int) $ids[$i], (string) $job['id'], $userId);
                $job['processed'] = (int) $job['processed'] + 1;
                $job['cursor'] = $i + 1;

                if ($result['error'] !== null) {
                    $job['failed'] = (int) $job['failed'] + 1;
                    $job['last_error'] = (string) $result['error'];
                } elseif (! empty($result['updated'])) {
                    $job['changed'] = (int) $job['changed'] + 1;
                }
            }

            if ((int) $job['cursor'] >= count($ids)) {
                $job['status'] = 'completed';
                $job['finished_at'] = current_time('mysql', true);
                $job['ids'] = [];
                update_option(self::OPTION, $job, false);
                return;
            }

            update_option(self::OPTION, $job, false);
            $this->schedule();
        } finally {
            delete_option(self::LOCK);
        }
    }

    public function cancel(): bool
    {
        $job = $this->state();
        if (! in_array((string) ($job['status'] ?? ''), ['queued', 'running'], true)) {
            return false;
        }

        $job['status'] = 'cancelled';
        $job['finished_at'] = current_time('mysql', true);
        $job['ids'] = [];
        update_option(self::OPTION, $job, false);
        wp_clear_scheduled_hook(self::HOOK);
        delete_option(self::LOCK);
        return true;
    }

    public function state(): array
    {
        $job = get_option(self::OPTION, []);
        return is_array($job) ? $job : [];
    }

    private function schedule(): void
    {
        if (! wp_next_scheduled(self::HOOK)) {
            wp_schedule_single_event(time() + 1, self::HOOK);
        }
    }

    private function acquireLock(): bool
    {
        if (add_option(self::LOCK, time(), '', false)) {
            return true;
        }

        $lockedAt = (int) get_option(self::LOCK, 0);
        if ($lockedAt > 0 && (time() - $lockedAt) > self::LOCK_TTL) {
            delete_option(self::LOCK);
            return add_option(self::LOCK, time(), '', false);
        }

        return false;
    }
}
