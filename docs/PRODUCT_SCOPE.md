# Product Scope — CatalogMend AI Free

## Problem

WooCommerce catalogs can contain damaged product text caused by failed imports, encoding mismatches, database migrations, copy/paste errors, broken feeds, or legacy integrations. The visible result can be unreadable fragments, replacement characters, malformed byte sequences, and mojibake.

Manual cleanup is slow and risky because catalog records also contain business-critical technical data that must not change.

## Product goal

Provide a safe WordPress-native workflow that finds damaged product text and removes only the corrupted fragments while preserving valid content and all technical WooCommerce data.

## Non-goals for Free

The Free edition will not:

- generate new marketing copy;
- reconstruct missing sentences with AI;
- rewrite or improve valid text;
- translate content;
- modify prices, inventory, attributes, variations, or other technical product data;
- edit product images;
- remove or replace logos from images;
- require an external AI API.

## Primary workflows

### 1. Scan

The administrator starts a scan for selected products or the catalog.

The scanner returns:

- product identifier;
- affected field;
- detected suspicious fragment/pattern;
- confidence/severity where applicable;
- proposed cleaned value;
- whether manual review is recommended.

### 2. Preview

Before write operations, the administrator can compare original and cleaned text.

### 3. Repair

The plugin removes fragments classified as corrupted by the deterministic detector. Valid text is not rephrased.

### 4. Batch repair

Large catalogs are processed in chunks using resumable jobs. One failed item must not invalidate the entire batch.

### 5. Audit

Each applied repair should record enough information to answer:

- what product changed;
- what field changed;
- when it changed;
- which rule triggered the change;
- whether the operation was manual or batch;
- success/failure status.

## Product invariants

1. The plugin changes only fields explicitly selected for text repair.
2. Technical WooCommerce data is immutable within the repair pipeline.
3. Detection and mutation are separate stages.
4. Dry-run mode performs no database writes.
5. Batch jobs are restartable.
6. Valid UTF-8 text must not be changed merely because it uses a non-Latin alphabet.
7. Hebrew, Russian, Arabic, European accented text and mixed-language product descriptions are first-class valid input.
8. HTML structure must be preserved unless a damaged fragment itself prevents safe preservation.

## Initial target fields

- `post_title`
- `post_excerpt`
- `post_content`

Support for custom fields/page builders/importer-specific fields is deferred until adapters are designed.

## UX requirements

The initial admin UI should provide:

- catalog health summary;
- scan button;
- filters by status/severity;
- product/field-level preview;
- dry-run mode;
- selected repair;
- batch repair;
- operation progress;
- failures/retry list;
- audit/history screen.

## Success criteria for v1

- Common corruption patterns are detected with low false-positive rates.
- A catalog can be scanned without timing out on normal shared/VPS WordPress hosting.
- The administrator can see exactly what will be removed before applying it.
- No technical WooCommerce fields are changed by the repair engine.
- Operations can resume after interruption.
