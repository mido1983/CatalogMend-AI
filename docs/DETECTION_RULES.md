# Corruption Detection Strategy — Free Edition

## Objective

Detect text that is likely to be damaged while minimizing false positives across multilingual WooCommerce catalogs.

CatalogMend must not equate "unusual Unicode" with corruption.

## Expected corruption classes

Initial detector research should cover:

- Unicode replacement character `�` (`U+FFFD`);
- malformed/imported byte artifacts;
- UTF-8 interpreted as Windows-1252/ISO-8859-1 mojibake;
- repeated mojibake sequences such as `Ã`, `Â`, `Ð`, `Ñ` when context indicates encoding damage;
- broken HTML entities;
- control characters that should not appear in product copy;
- obvious binary/garbage fragments introduced into text fields;
- repeated nonsensical character runs caused by failed migrations/imports.

## Important false-positive rule

A single character or script is not enough to classify content as damaged.

For example, `Ð` or `Ñ` can appear in mojibake, but Cyrillic text is valid. Detection must use sequence/context rules rather than globally deleting characters.

The same applies to Hebrew, Arabic, accented European languages, symbols, measurements, trademarks and mathematical characters.

## Detector model

Each detector should return a structured finding:

```text
rule_id
field
start/end or fragment reference
severity
confidence
reason
proposed_action
```

The repair layer consumes findings; it must not independently guess corruption.

## Severity proposal

- `high`: virtually certain corruption, safe candidate for automatic removal;
- `medium`: likely corruption, default to preview/review;
- `low`: suspicious but ambiguous, report only by default.

Thresholds must be validated against a multilingual fixture corpus before automatic repair is enabled.

## Test corpus

Before v1 release, build fixtures containing:

### Valid content

- English
- Hebrew
- Russian
- Arabic
- mixed RTL/LTR content
- accented Latin languages
- product specifications
- HTML
- shortcodes
- emoji
- currency symbols
- measurements/model numbers

### Damaged content

Generate controlled examples from common encoding failures and real anonymized samples where available.

Tests must verify both detection recall and non-destruction of valid content.

## Repair rule

The Free edition may remove a fragment only when:

1. a registered detector produced the finding;
2. the finding meets the configured automatic-action threshold;
3. removal does not require inventing replacement prose;
4. resulting text passes UTF-8/HTML validation.

If these conditions are not met, CatalogMend reports the issue rather than guessing.
