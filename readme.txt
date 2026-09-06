=== CatalogMend AI ===
Contributors: mido1983
Tags: woocommerce, cleanup, mojibake, encoding, products
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Safely detects and removes high-confidence corrupted characters from WooCommerce product text. No AI and no external API calls.

== Description ==

CatalogMend AI Free scans WooCommerce product titles, short descriptions, and descriptions for deterministic signs of text corruption.

The Free edition never sends catalog content to an AI service. It only automatically removes high-confidence corruption such as Unicode replacement characters and forbidden control characters. Ambiguous mojibake is reported for review instead of being guessed away.

Features:

* Paginated catalog scan.
* Before/after preview.
* Single-product cleanup.
* Selected-product background cleanup using WP-Cron.
* Progress and cancellation for background jobs.
* Audit history with original and applied values.
* Rollback of individual cleanup events.
* CSV audit export.
* Configurable batch size.
* Optional removal of CatalogMend data on uninstall.
* WooCommerce HPOS compatibility declaration.

CatalogMend only mutates allow-listed text fields. Prices, SKU, stock, attributes, variations, categories, media, and product metadata are not changed.

== Installation ==

1. Upload the plugin directory to `/wp-content/plugins/catalogmend-ai/` or install the ZIP through WordPress.
2. Activate CatalogMend AI.
3. Ensure WooCommerce is active.
4. Open WooCommerce > CatalogMend AI.
5. Scan and preview before applying cleanup.

== Frequently Asked Questions ==

= Does the Free edition use AI? =

No. It makes no external AI/API requests.

= Does it rewrite my descriptions? =

No. Free only removes explicitly safe corruption. Ambiguous encoding problems are shown for review.

= Can I undo a cleanup? =

Yes. Each successful cleanup is recorded in History and can be rolled back.

= Does it change prices or stock? =

No. Only title, short description, and description are in the mutation allow-list.

== Changelog ==

= 1.0.0 =
* Initial Free release.
* Deterministic corruption scanner and safe cleanup.
* Preview, batch jobs, audit, rollback, CSV export, settings, and uninstall policy.
