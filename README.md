# CatalogMend AI

CatalogMend AI is a WordPress/WooCommerce catalog repair plugin focused on recovering product content damaged by encoding errors, corrupted characters, mojibake, and other unreadable text fragments.

This repository contains the **Free / Community edition**.

## Core principle

CatalogMend must repair damaged catalog content without changing valid product data.

The Free edition is intentionally deterministic and does **not** use AI. It detects suspicious or corrupted text fragments and removes only the damaged content it can identify with configured rules.

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
- custom fields and third-party metadata
- images and media

The Free edition repairs text only.

## Target text fields

Initial target fields:

- product title
- short description
- full description

Additional fields must be opt-in and explicitly supported.

## Safety model

Catalog repair is destructive if implemented carelessly. The plugin therefore follows these rules:

1. Scan before write.
2. Show what will change.
3. Never silently rewrite valid content.
4. Keep original values available for rollback/audit where practical.
5. Batch operations must be resumable and must not depend on one long PHP request.
6. Technical WooCommerce data is outside the repair scope.

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

## Development status

Project initialization / specification phase.

See:

- `docs/PRODUCT_SCOPE.md`
- `docs/ARCHITECTURE.md`
- `docs/DETECTION_RULES.md`
- `docs/ROADMAP.md`

## Compatibility target

Initial implementation target:

- WordPress 6.x
- WooCommerce current supported releases
- PHP 8.1+
- MySQL/MariaDB versions supported by current WordPress/WooCommerce

Exact minimum versions will be frozen before the first public release.

## License

License is not yet finalized. Do not assume a license until a LICENSE file is added.
