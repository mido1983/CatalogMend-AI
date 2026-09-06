# CatalogMend AI

CatalogMend AI is a WordPress/WooCommerce catalog repair plugin focused on recovering product content damaged by encoding errors, corrupted characters, mojibake, and other unreadable text fragments.

This repository contains the **Free / Community edition**.

## Core principle

CatalogMend must repair damaged catalog content without changing valid product data.

The Free edition is deterministic and does **not** use AI. It scans supported text fields, reports suspicious fragments, and automatically removes only corruption classified as safe to delete.

## Current implementation — 0.1.0 development

Implemented in `main`:

- WordPress plugin bootstrap with PSR-4-style internal autoloading.
- WooCommerce admin page under **WooCommerce → CatalogMend AI**.
- Paginated product scan (50 products per page).
- Initial deterministic corruption rules.
- High-confidence detection for Unicode replacement characters and forbidden control bytes.
- Conservative reporting of ambiguous mojibake patterns without automatic deletion.
- HTML/comment/shortcode protection while visible text is inspected.
- Cleaning limited to product title, short description and full description.
- Nonce and `manage_woocommerce` checks for mutations.
- Snapshot of changed text fields before cleanup.
- One-step rollback of the last CatalogMend cleanup for a product.
- PHPUnit tests for core detection and markup preservation.
- GitHub Actions PHP lint/test workflow for PHP 8.1, 8.2 and 8.3.

Still to implement before the first usable beta:

- Full dry-run diff UI before every write.
- Resumable/background batch scan and cleanup.
- Audit/job persistence.
- Expanded multilingual mojibake fixture corpus and detection rules.
- WooCommerce dependency/compatibility checks.
- Bulk selection and filters.
- Packaging/release workflow.

## Free edition scope

- Scan WooCommerce product content for corrupted/unreadable fragments.
- Detect common mojibake and malformed encoding patterns.
- Preview detected problems before applying changes.
- Remove detected corrupted fragments without rewriting valid text.
- Process products individually or in batches.
- Keep technical product data unchanged.
- Produce an audit/report of affected products and applied changes.
- Support dry-run mode before database writes.

## Data that must remain unchanged

Unless a future feature explicitly says otherwise, CatalogMend must not alter:

- SKU
- product ID
- slug/permalink
- prices and sale prices
- stock values and stock status
- tax settings
- product type
- attributes and variations
- dimensions and weight
- categories and tags
- linked/up-sell/cross-sell relationships
- downloadable/virtual product settings
- custom fields and third-party metadata, except CatalogMend's own rollback/audit metadata
- images and media

The Free edition repairs text only.

## Target text fields

Initial target fields:

- product title
- short description
- full description

Additional fields must be opt-in and explicitly supported.

## Safety model

Catalog repair is destructive if implemented carelessly. The plugin follows these rules:

1. Scan before write.
2. Show what will change.
3. Never silently rewrite valid content.
4. Save changed source values for rollback.
5. Batch operations must be resumable and must not depend on one long PHP request.
6. Technical WooCommerce data is outside the repair scope.
7. Ambiguous corruption is report-only until a rule is proven safe enough for automatic removal.

## Editions

| Capability | Free | Pro |
|---|---:|---:|
| Corruption/mojibake detection | Yes | Yes |
| Remove damaged fragments | Yes | Yes |
| Dry run / preview | Yes | Yes |
| Batch processing | Yes | Yes |
| AI-assisted reconstruction | No | Yes |
| AI rewrite / text improvement | No | Yes |
| Product-image logo cleanup | No | Yes |
| Logo replacement workflow | No | Yes |

The commercial edition is developed separately in the private `CatalogMend-AI-pro` repository.

## Documentation

- `docs/PRODUCT_SCOPE.md`
- `docs/ARCHITECTURE.md`
- `docs/DETECTION_RULES.md`
- `docs/ROADMAP.md`

## Compatibility target

Initial implementation target:

- WordPress 6.4+
- WooCommerce current supported releases
- PHP 8.1+
- MySQL/MariaDB versions supported by current WordPress/WooCommerce

Exact WooCommerce minimum version will be frozen before the first public release.

## License

GPL-2.0-or-later.
