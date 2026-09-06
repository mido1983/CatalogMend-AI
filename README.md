# CatalogMend AI Free

CatalogMend AI Free is a WordPress/WooCommerce plugin for detecting and safely removing deterministic text corruption from product content.

Version: **1.0.0**

## What it does

CatalogMend scans WooCommerce product titles, short descriptions and full descriptions for encoding damage and unreadable fragments. The Free edition does not use AI and does not send catalog data to external services.

Automatic cleanup is deliberately conservative. High-confidence corruption such as the Unicode replacement character (`�`) and forbidden control characters can be removed automatically. Ambiguous mojibake is reported for review instead of being guessed away.

## Features

- Paginated catalog scan.
- Structured corruption findings with severity.
- Before/After preview without database writes.
- Safe single-product cleanup.
- Selected-product background cleanup using resumable WP-Cron jobs.
- Batch progress, failure count and cancellation.
- Dedicated audit/history table.
- Original/applied value storage for supported text fields.
- Rollback of individual cleanup events.
- CSV audit export.
- Configurable batch size.
- Optional data deletion on uninstall.
- WooCommerce HPOS compatibility declaration.
- PHP 8.1/8.2/8.3 CI matrix.

## Safety boundary

Free can mutate only:

- `post_title`
- `post_excerpt`
- `post_content`

It does **not** change SKU, product ID, slug, prices, sale prices, stock, tax configuration, product type, attributes, variations, dimensions, weight, categories, tags, linked products, downloadable settings, custom fields, third-party metadata, images or media.

HTML tags, HTML comments and WordPress shortcodes are treated as protected segments by the repair processor.

## Installation

1. Install and activate WooCommerce.
2. Download or clone this repository into `wp-content/plugins/catalogmend-ai`.
3. Activate **CatalogMend AI** in WordPress.
4. Open **WooCommerce → CatalogMend AI**.
5. Scan, preview and then apply cleanup where appropriate.

For background jobs, WordPress cron must be operational. If `DISABLE_WP_CRON` is enabled, configure a real server cron to invoke `wp-cron.php`.

## Admin workflow

### Scan & Repair

CatalogMend scans products in pages of 50 and shows only products with findings. Use **Preview** to inspect the exact Before/After result.

Use **Clean safe findings** for one product, or select products and start a background batch.

### History

Every successful cleanup creates an audit event. A `clean` event can be rolled back. Rollback also creates a new audit event, so history remains append-only.

### Settings

- Batch size: 1–100 products per WP-Cron step, default 20.
- Delete data on uninstall: disabled by default.

## Detection philosophy

Unusual Unicode is not automatically corruption. Hebrew, Arabic, Cyrillic, accented Latin text, symbols, emoji, measurements and mixed RTL/LTR content must not be removed just because they are non-ASCII.

See `docs/DETECTION_RULES.md` for the rule model.

## Development

```bash
composer install
composer lint
composer test
```

CI runs lint and PHPUnit on PHP 8.1, 8.2 and 8.3.

## Documentation

- `docs/USER_GUIDE.md`
- `docs/PRODUCT_SCOPE.md`
- `docs/ARCHITECTURE.md`
- `docs/DETECTION_RULES.md`
- `docs/ROADMAP.md`

## Release status

Free v1.0.0 implementation is complete. CI is required to remain green before release packaging.

## Pro edition

AI reconstruction/rewrite and product-image logo cleanup are intentionally excluded from this repository and belong to the separate Pro edition.

## License

GPL-2.0-or-later.
