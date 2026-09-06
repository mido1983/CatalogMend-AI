<?php

declare(strict_types=1);

namespace CatalogMend;

use CatalogMend\Admin\AdminPage;
use CatalogMend\Application\ProductCleaner;
use CatalogMend\Application\ProductScanner;
use CatalogMend\Domain\Detection\CorruptionDetector;
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

        $detector = new CorruptionDetector();
        $processor = new HtmlTextProcessor($detector);
        $scanner = new ProductScanner($processor);
        $cleaner = new ProductCleaner($processor);

        if (is_admin()) {
            (new AdminPage($scanner, $cleaner))->register();
        }
    }
}
