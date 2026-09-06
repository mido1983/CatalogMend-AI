# Roadmap — CatalogMend AI Free

## Phase 0 — Specification

- Define immutable technical data boundary.
- Define supported text fields.
- Build corruption taxonomy.
- Build multilingual clean/damaged fixture corpus.
- Freeze minimum WordPress/WooCommerce/PHP compatibility for first release.

## Phase 1 — Plugin foundation

- WordPress plugin bootstrap.
- Namespaces/autoloading.
- Admin capability model.
- Activation/deactivation hooks.
- Settings foundation.
- Test framework and CI.

## Phase 2 — Scanner

- WooCommerce product source adapter.
- UTF-8-aware text extraction.
- HTML/text-node handling.
- Deterministic corruption detectors.
- Structured findings.
- Product-level and catalog-level scan.

## Phase 3 — Safe repair

- Repair proposal engine.
- Before/after diff.
- Dry-run mode.
- Allow-listed field mutation.
- Validation before write.
- Manual single-product apply.

## Phase 4 — Batch processing

- Resumable job model.
- Action Scheduler integration or equivalent.
- Progress UI.
- Retry/failure handling.
- Cancellation.
- Concurrency protection.

## Phase 5 — Audit and rollback

- Repair history.
- Original/proposed/applied value tracking according to storage policy.
- Product and batch filters.
- Rollback strategy for supported changes.

## Phase 6 — Public release hardening

- Large-catalog performance tests.
- Multilingual false-positive tests.
- WooCommerce compatibility testing.
- Security review.
- Uninstall/data-retention behavior.
- WordPress.org packaging requirements if distributed there.
- User documentation.

## Post-v1 candidates

- Additional product text adapters.
- Importer-specific repair profiles.
- WP-CLI scan/repair commands.
- Scheduled health scans.
- Exportable catalog health reports.
- Ruleset extension API.

AI rewriting, text generation and image manipulation remain Pro-only capabilities.
