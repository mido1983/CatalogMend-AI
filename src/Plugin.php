<?php

declare(strict_types=1);

namespace CatalogMend;

use CatalogMend\Admin\AdminPage;
use CatalogMend\Application\BatchJobService;
use CatalogMend\Application\ProductCleaner;
use CatalogMend\Application\ProductScanner;
use CatalogMend\Domain\Detection\CorruptionDetector;
use CatalogMend\Infrastructure\Persistence\AuditRepository;
use CatalogMend\Support\HtmlTextProcessor;

final class Plugin
{
    private static ?self $instance = null;
    private bool $booted = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        load_plugin_textdomain('catalogmend-ai', false, dirname(plugin_basename(CATALOGMEND_AI_FILE)) . '/languages');

        $detector = new CorruptionDetector();
        $processor = new HtmlTextProcessor($detector);
        $audit = new AuditRepository();
        $scanner = new ProductScanner($processor);
        $cleaner = new ProductCleaner($processor, $audit);
        $batch = new BatchJobService($cleaner);
        $batch->register();

        if (is_admin()) {
            (new AdminPage($scanner, $cleaner, $batch, $audit))->register();
        }
    }
}
