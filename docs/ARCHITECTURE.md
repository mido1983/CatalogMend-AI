# Architecture — CatalogMend AI Free

## Design goals

- Safe by default.
- Deterministic behavior.
- WordPress/WooCommerce compatibility.
- No dependency on external AI services.
- Resumable processing for large catalogs.
- Strict separation between text repair and technical product data.

## Proposed module layout

```text
catalogmend-ai/
├─ catalogmend-ai.php
├─ src/
│  ├─ Admin/
│  ├─ Application/
│  ├─ Domain/
│  │  ├─ Detection/
│  │  ├─ Repair/
│  │  └─ Audit/
│  ├─ Infrastructure/
│  │  ├─ WordPress/
│  │  ├─ WooCommerce/
│  │  └─ Persistence/
│  └─ Support/
├─ assets/
├─ languages/
├─ tests/
└─ docs/
```

Final structure may change during implementation, but boundaries should remain explicit.

## Processing pipeline

### 1. Product selection

Resolve a set of WooCommerce product IDs according to filters or explicit selection.

### 2. Snapshot

Read only supported text fields and create an internal immutable source snapshot.

### 3. Detection

Run deterministic detectors against text nodes. Detection must be aware of UTF-8 and HTML boundaries.

### 4. Repair proposal

Produce a proposed value without writing to WordPress.

### 5. Validation

Validate that:

- output is valid UTF-8;
- supported HTML remains structurally acceptable;
- product identity is unchanged;
- no unsupported field is included in the mutation set;
- changes are bounded to detected text fragments.

### 6. Preview or write

Dry run stops here and returns the diff. Apply mode writes only the approved field value.

### 7. Audit

Persist operation metadata and status.

## Data boundaries

### Mutable in Free

Only explicitly enabled text fields:

- title;
- short description;
- description.

### Immutable in Free

All WooCommerce technical/business data, including product meta not explicitly registered as a supported text adapter.

The mutation layer must use an allow-list, not a block-list.

## HTML handling

Product descriptions can contain HTML, shortcodes and builder markup. The detector must avoid treating markup syntax as corrupted text.

Preferred approach:

1. tokenize/parse text versus markup;
2. inspect user-visible text nodes;
3. preserve tags, attributes and shortcodes byte-for-byte when possible;
4. reconstruct only the changed text node;
5. validate before write.

Regex-only replacement across raw HTML is not acceptable as the sole repair strategy.

## Batch jobs

Do not process a full catalog in one synchronous request.

Requirements:

- fixed-size chunks;
- persisted cursor/state;
- idempotent item processing;
- retryable failures;
- per-item result status;
- lock to avoid two workers mutating the same product concurrently;
- cancellation support.

WordPress Action Scheduler is a strong candidate because WooCommerce already ships and uses it. Final dependency choice should be validated during implementation.

## Persistence

Initial custom tables are expected for jobs/audit rather than overloading product meta.

Suggested entities:

- scan job;
- scan item;
- repair event;
- optional original-value snapshot/rollback record.

Schema will be frozen only after the implementation spike.

## Security

All admin actions must enforce:

- WordPress capability checks;
- nonces for browser-triggered mutations;
- sanitization/validation at boundaries;
- escaping on output;
- prepared SQL when direct database access is unavoidable.

No catalog mutation endpoint may be publicly callable without authorization.

## Extensibility

The Free core should expose internal interfaces that allow Pro to add AI and image pipelines without forking core business rules unnecessarily.

Candidate abstractions:

- `TextSourceAdapter`
- `CorruptionDetector`
- `RepairStrategy`
- `RepairValidator`
- `JobRunner`
- `AuditStore`

The public extension API should not be declared stable before v1.0.
