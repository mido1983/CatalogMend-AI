# Roadmap — CatalogMend AI Free

## v1.0.0 — Complete

### Specification
- [x] Immutable WooCommerce technical-data boundary.
- [x] Supported text-field allow-list.
- [x] Corruption taxonomy and conservative severity model.
- [x] PHP/WordPress/WooCommerce compatibility targets.

### Plugin foundation
- [x] WordPress bootstrap and autoloading.
- [x] WooCommerce dependency check.
- [x] Admin capability and nonce enforcement.
- [x] Activation/deactivation hooks.
- [x] Settings foundation.
- [x] PHPUnit and CI matrix.

### Scanner
- [x] Product source scan.
- [x] UTF-8-aware deterministic detectors.
- [x] Protected HTML/comments/shortcode segments.
- [x] Structured findings.
- [x] Paginated catalog scan.

### Safe repair
- [x] Before/After preview.
- [x] Dry-run behavior through scan/preview.
- [x] Explicit mutation allow-list.
- [x] UTF-8 validation before write.
- [x] Single-product cleanup.
- [x] Ambiguous findings remain review-only.

### Batch processing
- [x] Persisted resumable job state.
- [x] WP-Cron worker.
- [x] Configurable chunk size.
- [x] Progress UI.
- [x] Per-item failure accounting.
- [x] Cancellation.
- [x] Worker lock to avoid concurrent mutation.

### Audit and rollback
- [x] Dedicated audit table.
- [x] Before/After values for changed supported fields.
- [x] User, batch and timestamp metadata.
- [x] History UI.
- [x] Rollback for cleanup events.
- [x] CSV audit metadata export.

### Release hardening
- [x] WooCommerce HPOS compatibility declaration.
- [x] Optional uninstall cleanup policy.
- [x] WordPress-style `readme.txt`.
- [x] User guide.
- [x] PHP 8.1/8.2/8.3 lint and unit-test CI.

## Deferred post-v1 candidates

These are enhancements, not blockers for Free v1.0.0:

- Additional product text adapters.
- Importer-specific repair profiles.
- WP-CLI commands.
- Scheduled health scans.
- Richer health-report exports.
- Public ruleset extension API.
- Larger real-world multilingual fixture corpus and performance benchmarking.

AI rewriting, generated replacement prose and image manipulation remain Pro-only capabilities.
