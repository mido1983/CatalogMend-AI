<?php

declare(strict_types=1);

namespace CatalogMend\Admin;

use CatalogMend\Application\ProductCleaner;
use CatalogMend\Application\ProductScanner;

final class AdminPage
{
    private const SLUG = 'catalogmend-ai';

    public function __construct(
        private readonly ProductScanner $scanner,
        private readonly ProductCleaner $cleaner
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_post_catalogmend_clean_product', [$this, 'handleClean']);
        add_action('admin_post_catalogmend_rollback_product', [$this, 'handleRollback']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('CatalogMend AI', 'catalogmend-ai'),
            __('CatalogMend AI', 'catalogmend-ai'),
            'manage_woocommerce',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'catalogmend-ai'));
        }

        $page = isset($_GET['cm_page']) ? max(1, absint($_GET['cm_page'])) : 1;
        $scan = $this->scanner->scan($page, 50);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('CatalogMend AI — Free', 'catalogmend-ai'); ?></h1>
            <p><?php echo esc_html__('Deterministic scan only. Free edition never sends catalog data to an AI service.', 'catalogmend-ai'); ?></p>

            <?php $this->renderNotice(); ?>

            <div class="notice notice-info inline"><p>
                <?php echo esc_html(sprintf(
                    __('Scanned %1$d products on this page. Catalog total: %2$d.', 'catalogmend-ai'),
                    $scan['scanned'],
                    $scan['total']
                )); ?>
            </p></div>

            <?php if ($scan['items'] === []) : ?>
                <p><?php echo esc_html__('No suspicious text was found in this batch.', 'catalogmend-ai'); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead><tr>
                        <th><?php echo esc_html__('Product', 'catalogmend-ai'); ?></th>
                        <th><?php echo esc_html__('Findings', 'catalogmend-ai'); ?></th>
                        <th><?php echo esc_html__('Action', 'catalogmend-ai'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($scan['items'] as $item) : ?>
                        <tr>
                            <td>
                                <strong>#<?php echo esc_html((string) $item['id']); ?></strong>
                                <?php echo esc_html((string) $item['title']); ?><br>
                                <a href="<?php echo esc_url(get_edit_post_link((int) $item['id']) ?: ''); ?>">
                                    <?php echo esc_html__('Edit product', 'catalogmend-ai'); ?>
                                </a>
                            </td>
                            <td><?php $this->renderFindings($item['fields']); ?></td>
                            <td>
                                <?php if ($this->hasAutoRemovable($item['fields'])) : ?>
                                    <?php $this->renderCleanForm((int) $item['id']); ?>
                                <?php else : ?>
                                    <span><?php echo esc_html__('Review only — ambiguous corruption will not be deleted automatically.', 'catalogmend-ai'); ?></span>
                                <?php endif; ?>
                                <?php if ($this->cleaner->canRollback((int) $item['id'])) : ?>
                                    <br><?php $this->renderRollbackForm((int) $item['id']); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php $this->renderPagination((int) $scan['page'], (int) $scan['pages']); ?>
        </div>
        <?php
    }

    public function handleClean(): void
    {
        $productId = $this->authorizedProductId('catalogmend_clean_product_');
        $result = $this->cleaner->clean($productId);
        $status = $result['error'] !== null ? 'error' : ($result['updated'] ? 'cleaned' : 'unchanged');
        $this->redirect($status, $productId);
    }

    public function handleRollback(): void
    {
        $productId = $this->authorizedProductId('catalogmend_rollback_product_');
        $result = $this->cleaner->rollback($productId);
        $this->redirect($result['error'] === null && $result['updated'] ? 'rolled_back' : 'error', $productId);
    }

    private function authorizedProductId(string $nonceActionPrefix): int
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'catalogmend-ai'));
        }

        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        check_admin_referer($nonceActionPrefix . $productId);
        return $productId;
    }

    private function redirect(string $status, int $productId): void
    {
        wp_safe_redirect(add_query_arg([
            'page' => self::SLUG,
            'catalogmend_status' => $status,
            'product_id' => $productId,
        ], admin_url('admin.php')));
        exit;
    }

    private function renderNotice(): void
    {
        $status = isset($_GET['catalogmend_status']) ? sanitize_key((string) $_GET['catalogmend_status']) : '';
        $messages = [
            'cleaned' => ['success', __('Safe corrupted fragments were removed. A rollback snapshot was saved.', 'catalogmend-ai')],
            'rolled_back' => ['success', __('The last CatalogMend cleanup was rolled back.', 'catalogmend-ai')],
            'unchanged' => ['info', __('No automatically removable corruption was found.', 'catalogmend-ai')],
            'error' => ['error', __('The product could not be updated.', 'catalogmend-ai')],
        ];

        if (! isset($messages[$status])) {
            return;
        }

        [$type, $message] = $messages[$status];
        printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($type), esc_html($message));
    }

    private function renderFindings(array $fields): void
    {
        $labels = [
            'post_title' => __('Title', 'catalogmend-ai'),
            'post_excerpt' => __('Short description', 'catalogmend-ai'),
            'post_content' => __('Description', 'catalogmend-ai'),
        ];

        foreach ($fields as $field => $findings) {
            echo '<strong>' . esc_html($labels[$field] ?? $field) . '</strong><ul>';
            foreach ($findings as $finding) {
                printf(
                    '<li><code>%1$s</code> — %2$s (%3$s)</li>',
                    esc_html($finding['rule_id']),
                    esc_html($finding['reason']),
                    esc_html($finding['severity'])
                );
            }
            echo '</ul>';
        }
    }

    private function renderCleanForm(int $productId): void
    {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="catalogmend_clean_product">
            <input type="hidden" name="product_id" value="<?php echo esc_attr((string) $productId); ?>">
            <?php wp_nonce_field('catalogmend_clean_product_' . $productId); ?>
            <?php submit_button(__('Remove safe corruption', 'catalogmend-ai'), 'secondary', 'submit', false); ?>
        </form>
        <?php
    }

    private function renderRollbackForm(int $productId): void
    {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="catalogmend_rollback_product">
            <input type="hidden" name="product_id" value="<?php echo esc_attr((string) $productId); ?>">
            <?php wp_nonce_field('catalogmend_rollback_product_' . $productId); ?>
            <?php submit_button(__('Rollback last cleanup', 'catalogmend-ai'), 'link-delete', 'submit', false); ?>
        </form>
        <?php
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

    private function renderPagination(int $page, int $pages): void
    {
        if ($pages <= 1) {
            return;
        }

        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo wp_kses_post(paginate_links([
            'base' => add_query_arg(['page' => self::SLUG, 'cm_page' => '%#%'], admin_url('admin.php')),
            'format' => '',
            'current' => $page,
            'total' => $pages,
        ]) ?: '');
        echo '</div></div>';
    }
}
