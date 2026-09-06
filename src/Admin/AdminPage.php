<?php

declare(strict_types=1);

namespace CatalogMend\Admin;

use CatalogMend\Application\BatchJobService;
use CatalogMend\Application\ProductCleaner;
use CatalogMend\Application\ProductScanner;
use CatalogMend\Infrastructure\Persistence\AuditRepository;

final class AdminPage
{
    private const SLUG = 'catalogmend-ai';

    public function __construct(
        private readonly ProductScanner $scanner,
        private readonly ProductCleaner $cleaner,
        private readonly BatchJobService $batch,
        private readonly AuditRepository $audit
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_head', [$this, 'renderMenuStyles']);
        add_action('admin_post_catalogmend_clean_product', [$this, 'handleClean']);
        add_action('admin_post_catalogmend_start_batch', [$this, 'handleStartBatch']);
        add_action('admin_post_catalogmend_cancel_batch', [$this, 'handleCancelBatch']);
        add_action('admin_post_catalogmend_rollback', [$this, 'handleRollback']);
        add_action('admin_post_catalogmend_export_audit', [$this, 'handleExport']);
        add_action('admin_post_catalogmend_save_settings', [$this, 'handleSaveSettings']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            __('CatalogMend AI', 'catalogmend-ai'),
            __('CatalogMend AI', 'catalogmend-ai'),
            'manage_woocommerce',
            self::SLUG,
            [$this, 'render'],
            'dashicons-admin-tools',
            56
        );
    }

    public function renderMenuStyles(): void
    {
        echo '<style id="catalogmend-admin-menu-styles">'
            . '#toplevel_page_catalogmend-ai{border-top:1px solid rgba(240,246,252,.25);'
            . 'border-bottom:1px solid rgba(240,246,252,.25);margin:6px 0;padding:4px 0}'
            . '</style>';
    }

    public function render(): void
    {
        $this->assertCapability();
        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'scan';
        if (! in_array($tab, ['scan', 'history', 'settings'], true)) {
            $tab = 'scan';
        }

        $job = $this->batch->state();
        if (in_array((string) ($job['status'] ?? ''), ['queued', 'running'], true)) {
            echo '<meta http-equiv="refresh" content="3">';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('CatalogMend AI — Free', 'catalogmend-ai'); ?></h1>
            <p><?php echo esc_html__('Deterministic WooCommerce text repair. No catalog data is sent to AI services.', 'catalogmend-ai'); ?></p>
            <?php $this->renderNotice(); ?>
            <?php $this->renderJob($job); ?>
            <nav class="nav-tab-wrapper">
                <?php $this->tabLink('scan', __('Scan & Repair', 'catalogmend-ai'), $tab); ?>
                <?php $this->tabLink('history', __('History', 'catalogmend-ai'), $tab); ?>
                <?php $this->tabLink('settings', __('Settings', 'catalogmend-ai'), $tab); ?>
            </nav>
            <?php
            if ($tab === 'history') {
                $this->renderHistory();
            } elseif ($tab === 'settings') {
                $this->renderSettings();
            } else {
                $this->renderScan();
            }
            ?>
        </div>
        <?php
    }

    public function handleClean(): void
    {
        $this->assertCapability();
        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        check_admin_referer('catalogmend_clean_product_' . $productId);
        $result = $this->cleaner->clean($productId);
        $status = $result['error'] !== null ? 'error' : ($result['updated'] ? 'cleaned' : 'unchanged');
        $this->redirect(['catalogmend_status' => $status]);
    }

    public function handleStartBatch(): void
    {
        $this->assertCapability();
        check_admin_referer('catalogmend_start_batch');

        $singleId = isset($_POST['single_product_id']) ? absint($_POST['single_product_id']) : 0;
        if ($singleId > 0) {
            $result = $this->cleaner->clean($singleId);
            $status = $result['error'] !== null ? 'error' : ($result['updated'] ? 'cleaned' : 'unchanged');
            $this->redirect(['catalogmend_status' => $status]);
        }

        $ids = isset($_POST['product_ids']) && is_array($_POST['product_ids'])
            ? array_map('absint', wp_unslash($_POST['product_ids']))
            : [];
        $result = $this->batch->start($ids);
        $this->redirect(['catalogmend_status' => $result['started'] ? 'batch_started' : 'batch_error']);
    }

    public function handleCancelBatch(): void
    {
        $this->assertCapability();
        check_admin_referer('catalogmend_cancel_batch');
        $this->batch->cancel();
        $this->redirect(['catalogmend_status' => 'batch_cancelled']);
    }

    public function handleRollback(): void
    {
        $this->assertCapability();
        $auditId = isset($_POST['audit_id']) ? absint($_POST['audit_id']) : 0;
        check_admin_referer('catalogmend_rollback_' . $auditId);
        $result = $this->cleaner->rollback($auditId);
        $this->redirect(['tab' => 'history', 'catalogmend_status' => $result['updated'] ? 'rolled_back' : 'rollback_error']);
    }

    public function handleSaveSettings(): void
    {
        $this->assertCapability();
        check_admin_referer('catalogmend_save_settings');
        $batchSize = isset($_POST['batch_size']) ? absint($_POST['batch_size']) : 20;
        update_option('catalogmend_batch_size', max(1, min(100, $batchSize)), false);
        update_option('catalogmend_delete_data_on_uninstall', isset($_POST['delete_data']) ? '1' : '0', false);
        $this->redirect(['tab' => 'settings', 'catalogmend_status' => 'settings_saved']);
    }

    public function handleExport(): void
    {
        $this->assertCapability();
        check_admin_referer('catalogmend_export_audit');

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="catalogmend-audit-' . gmdate('Y-m-d-His') . '.csv"');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            wp_die(esc_html__('Could not open export stream.', 'catalogmend-ai'));
        }

        fputcsv($out, ['id', 'product_id', 'operation', 'changed_fields', 'user_id', 'batch_id', 'created_at']);
        foreach ($this->audit->exportRows() as $row) {
            fputcsv($out, [
                $row['id'],
                $row['product_id'],
                $row['operation'],
                implode('|', $row['changed_fields']),
                $row['user_id'],
                $row['batch_id'],
                $row['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function renderScan(): void
    {
        $page = isset($_GET['cm_page']) ? max(1, absint($_GET['cm_page'])) : 1;
        $scan = $this->scanner->scan($page, 50);
        $previewId = isset($_GET['preview']) ? absint($_GET['preview']) : 0;

        if ($previewId > 0) {
            $this->renderPreview($previewId);
        }
        ?>
        <div class="notice notice-info inline"><p><?php
            echo esc_html(sprintf(
                __('Scanned %1$d products on this page. Catalog total: %2$d.', 'catalogmend-ai'),
                $scan['scanned'],
                $scan['total']
            ));
        ?></p></div>
        <?php if ($scan['items'] === []) : ?>
            <p><?php echo esc_html__('No suspicious text was found in this batch.', 'catalogmend-ai'); ?></p>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="catalogmend_start_batch">
                <?php wp_nonce_field('catalogmend_start_batch'); ?>
                <table class="widefat striped">
                    <thead><tr><td class="check-column"><input type="checkbox" onclick="document.querySelectorAll('.catalogmend-product').forEach(c=>c.checked=this.checked)"></td><th><?php echo esc_html__('Product', 'catalogmend-ai'); ?></th><th><?php echo esc_html__('Findings', 'catalogmend-ai'); ?></th><th><?php echo esc_html__('Actions', 'catalogmend-ai'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($scan['items'] as $item) : ?>
                        <tr>
                            <th class="check-column"><input class="catalogmend-product" type="checkbox" name="product_ids[]" value="<?php echo esc_attr((string) $item['id']); ?>"></th>
                            <td><strong>#<?php echo esc_html((string) $item['id']); ?></strong> <?php echo esc_html((string) $item['title']); ?><br><a href="<?php echo esc_url(get_edit_post_link((int) $item['id']) ?: ''); ?>"><?php echo esc_html__('Edit product', 'catalogmend-ai'); ?></a></td>
                            <td><?php $this->renderFindings($item['fields']); ?></td>
                            <td>
                                <a class="button" href="<?php echo esc_url(add_query_arg(['page' => self::SLUG, 'preview' => (int) $item['id']], admin_url('admin.php'))); ?>"><?php echo esc_html__('Preview', 'catalogmend-ai'); ?></a>
                                <?php if ($this->hasAutoRemovable($item['fields'])) : ?>
                                    <button class="button" type="submit" name="single_product_id" value="<?php echo esc_attr((string) $item['id']); ?>"><?php echo esc_html__('Clean safe findings', 'catalogmend-ai'); ?></button>
                                <?php else : ?>
                                    <span><?php echo esc_html__('Review only', 'catalogmend-ai'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p><?php submit_button(__('Clean selected safe findings', 'catalogmend-ai'), 'primary', 'submit', false); ?></p>
            </form>
        <?php endif; ?>
        <?php $this->renderPagination((int) $scan['page'], (int) $scan['pages'], 'scan'); ?>
        <?php
    }

    private function renderPreview(int $productId): void
    {
        $preview = $this->scanner->preview($productId);
        if ($preview === []) {
            return;
        }

        echo '<div class="card" style="max-width:none;margin-top:16px"><h2>' . esc_html(sprintf(__('Preview product #%d', 'catalogmend-ai'), $productId)) . '</h2>';
        $labels = ['post_title' => __('Title', 'catalogmend-ai'), 'post_excerpt' => __('Short description', 'catalogmend-ai'), 'post_content' => __('Description', 'catalogmend-ai')];
        foreach ($preview as $field => $values) {
            if (empty($values['changed'])) {
                continue;
            }
            echo '<h3>' . esc_html($labels[$field] ?? $field) . '</h3><table class="widefat"><thead><tr><th>' . esc_html__('Before', 'catalogmend-ai') . '</th><th>' . esc_html__('After', 'catalogmend-ai') . '</th></tr></thead><tbody><tr><td><pre style="white-space:pre-wrap">' . esc_html((string) $values['before']) . '</pre></td><td><pre style="white-space:pre-wrap">' . esc_html((string) $values['after']) . '</pre></td></tr></tbody></table>';
        }
        echo '</div>';
    }

    private function renderHistory(): void
    {
        $page = isset($_GET['cm_page']) ? max(1, absint($_GET['cm_page'])) : 1;
        $history = $this->audit->recent($page, 30);
        ?>
        <p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=catalogmend_export_audit'), 'catalogmend_export_audit')); ?>"><?php echo esc_html__('Export CSV', 'catalogmend-ai'); ?></a></p>
        <table class="widefat striped"><thead><tr><th>ID</th><th><?php echo esc_html__('Product', 'catalogmend-ai'); ?></th><th><?php echo esc_html__('Operation', 'catalogmend-ai'); ?></th><th><?php echo esc_html__('Fields', 'catalogmend-ai'); ?></th><th><?php echo esc_html__('Date (UTC)', 'catalogmend-ai'); ?></th><th><?php echo esc_html__('Action', 'catalogmend-ai'); ?></th></tr></thead><tbody>
        <?php foreach ($history['items'] as $row) : ?>
            <tr><td><?php echo esc_html((string) $row['id']); ?></td><td>#<?php echo esc_html((string) $row['product_id']); ?> <?php echo esc_html(get_the_title((int) $row['product_id'])); ?></td><td><?php echo esc_html((string) $row['operation']); ?></td><td><?php echo esc_html(implode(', ', $row['changed_fields'])); ?></td><td><?php echo esc_html((string) $row['created_at']); ?></td><td>
            <?php if ($row['operation'] === 'clean') : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="catalogmend_rollback"><input type="hidden" name="audit_id" value="<?php echo esc_attr((string) $row['id']); ?>"><?php wp_nonce_field('catalogmend_rollback_' . (int) $row['id']); ?><?php submit_button(__('Rollback', 'catalogmend-ai'), 'secondary', 'submit', false); ?></form>
            <?php endif; ?>
            </td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <?php $this->renderPagination((int) $history['page'], (int) $history['pages'], 'history'); ?>
        <?php
    }

    private function renderSettings(): void
    {
        $batchSize = (int) get_option('catalogmend_batch_size', 20);
        $deleteData = get_option('catalogmend_delete_data_on_uninstall', '0') === '1';
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:20px">
            <input type="hidden" name="action" value="catalogmend_save_settings">
            <?php wp_nonce_field('catalogmend_save_settings'); ?>
            <table class="form-table"><tr><th><label for="batch_size"><?php echo esc_html__('Batch size', 'catalogmend-ai'); ?></label></th><td><input id="batch_size" name="batch_size" type="number" min="1" max="100" value="<?php echo esc_attr((string) $batchSize); ?>"><p class="description"><?php echo esc_html__('Products processed per background WP-Cron step.', 'catalogmend-ai'); ?></p></td></tr><tr><th><?php echo esc_html__('Uninstall', 'catalogmend-ai'); ?></th><td><label><input type="checkbox" name="delete_data" value="1" <?php checked($deleteData); ?>> <?php echo esc_html__('Delete CatalogMend audit/history data when the plugin is uninstalled.', 'catalogmend-ai'); ?></label></td></tr></table>
            <?php submit_button(__('Save settings', 'catalogmend-ai')); ?>
        </form>
        <?php
    }

    private function renderJob(array $job): void
    {
        if ($job === []) {
            return;
        }
        $status = (string) ($job['status'] ?? '');
        $total = max(0, (int) ($job['total'] ?? 0));
        $processed = max(0, (int) ($job['processed'] ?? 0));
        $percent = $total > 0 ? min(100, (int) floor(($processed / $total) * 100)) : 0;
        echo '<div class="notice notice-info inline"><p><strong>' . esc_html__('Batch job:', 'catalogmend-ai') . '</strong> ' . esc_html($status) . ' — ' . esc_html("{$processed}/{$total} ({$percent}%)") . ', ' . esc_html__('changed', 'catalogmend-ai') . ': ' . esc_html((string) ($job['changed'] ?? 0)) . ', ' . esc_html__('failed', 'catalogmend-ai') . ': ' . esc_html((string) ($job['failed'] ?? 0)) . '.</p>';
        if (in_array($status, ['queued', 'running'], true)) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="catalogmend_cancel_batch">';
            wp_nonce_field('catalogmend_cancel_batch');
            submit_button(__('Cancel batch', 'catalogmend-ai'), 'secondary', 'submit', false);
            echo '</form>';
        }
        echo '</div>';
    }

    private function renderNotice(): void
    {
        $status = isset($_GET['catalogmend_status']) ? sanitize_key((string) $_GET['catalogmend_status']) : '';
        $messages = [
            'cleaned' => ['success', __('Safe corrupted fragments were removed.', 'catalogmend-ai')],
            'unchanged' => ['info', __('No automatically removable corruption was found.', 'catalogmend-ai')],
            'error' => ['error', __('The product could not be updated.', 'catalogmend-ai')],
            'batch_started' => ['success', __('Background cleanup started.', 'catalogmend-ai')],
            'batch_error' => ['error', __('Background cleanup could not be started.', 'catalogmend-ai')],
            'batch_cancelled' => ['info', __('Background cleanup cancelled.', 'catalogmend-ai')],
            'rolled_back' => ['success', __('The selected cleanup was rolled back.', 'catalogmend-ai')],
            'rollback_error' => ['error', __('Rollback failed.', 'catalogmend-ai')],
            'settings_saved' => ['success', __('Settings saved.', 'catalogmend-ai')],
        ];
        if (! isset($messages[$status])) {
            return;
        }
        [$type, $message] = $messages[$status];
        printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($type), esc_html($message));
    }

    private function renderFindings(array $fields): void
    {
        $labels = ['post_title' => __('Title', 'catalogmend-ai'), 'post_excerpt' => __('Short description', 'catalogmend-ai'), 'post_content' => __('Description', 'catalogmend-ai')];
        foreach ($fields as $field => $findings) {
            echo '<strong>' . esc_html($labels[$field] ?? $field) . '</strong><ul>';
            foreach ($findings as $finding) {
                printf('<li><code>%1$s</code> — %2$s (%3$s)</li>', esc_html($finding['rule_id']), esc_html($finding['reason']), esc_html($finding['severity']));
            }
            echo '</ul>';
        }
    }

    private function hasAutoRemovable(array $fields): bool
    {
        foreach ($fields as $findings) {
            foreach ($findings as $finding) {
                if (! empty($finding['auto_remove'])) {
                    return true;
                }
            }
        }
        return false;
    }

    private function renderPagination(int $page, int $pages, string $tab): void
    {
        if ($pages <= 1) {
            return;
        }
        echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(paginate_links([
            'base' => add_query_arg(['page' => self::SLUG, 'tab' => $tab, 'cm_page' => '%#%'], admin_url('admin.php')),
            'format' => '', 'current' => $page, 'total' => $pages,
        ]) ?: '') . '</div></div>';
    }

    private function tabLink(string $tab, string $label, string $current): void
    {
        $class = 'nav-tab' . ($tab === $current ? ' nav-tab-active' : '');
        echo '<a class="' . esc_attr($class) . '" href="' . esc_url(add_query_arg(['page' => self::SLUG, 'tab' => $tab], admin_url('admin.php'))) . '">' . esc_html($label) . '</a>';
    }

    private function assertCapability(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'catalogmend-ai'));
        }
    }

    private function redirect(array $args = []): never
    {
        wp_safe_redirect(add_query_arg(array_merge(['page' => self::SLUG], $args), admin_url('admin.php')));
        exit;
    }
}
