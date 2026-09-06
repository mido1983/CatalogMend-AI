# CatalogMend AI Free — User Guide

## Installation

1. Install and activate WooCommerce.
2. Install CatalogMend AI Free.
3. Activate the plugin.
4. Open **WooCommerce → CatalogMend AI**.

CatalogMend will not boot its repair UI if WooCommerce is inactive.

## Scan & Repair

The Scan & Repair tab scans WooCommerce products in pages of 50.

Supported fields:

- product title;
- short description;
- full description.

The scanner reports suspicious fragments but only marks deterministic high-confidence corruption as automatically removable.

### Preview

Use **Preview** before applying a change. Preview shows Before and After values for fields that would actually change.

### Single cleanup

Use **Clean safe findings** on one product. Only auto-removable findings are removed.

### Batch cleanup

Select products and choose **Clean selected safe findings**. CatalogMend creates a resumable background job using WP-Cron. The page shows processed, changed and failed counts. A running batch can be cancelled.

## What Free never changes

CatalogMend Free does not mutate SKU, IDs, slug, prices, stock, taxes, product type, attributes, variations, dimensions, weight, categories, tags, linked products, downloads, custom metadata, images or media.

## History and rollback

Every successful cleanup is stored in the CatalogMend audit table with original and applied values. Open **History** to inspect events.

For a `clean` event, use **Rollback** to restore the original values captured by that event. Rollback itself is written as a new audit event.

Use **Export CSV** to export audit metadata. The CSV intentionally contains event metadata and changed field names, not full product copy.

## Settings

### Batch size

Controls how many selected products are processed in one WP-Cron step. Default: 20. Allowed range: 1–100.

Lower values reduce request load on smaller hosting environments. Higher values complete faster on sufficiently provisioned servers.

### Delete data on uninstall

Disabled by default. If enabled before uninstalling the plugin, CatalogMend removes its audit table, job state and settings. If disabled, audit data is retained.

## Safety behavior

- Scan does not write data.
- Preview does not write data.
- Ambiguous mojibake is review-only.
- HTML tags, HTML comments and WordPress shortcodes are treated as protected segments by the repair processor.
- Mutation is restricted to the explicit text-field allow-list.
- Browser-triggered writes require `manage_woocommerce` and a valid WordPress nonce.

## WP-Cron note

Background cleanup requires WordPress cron to run. On sites where `DISABLE_WP_CRON` is enabled, the server should invoke `wp-cron.php` using a real system cron schedule.
