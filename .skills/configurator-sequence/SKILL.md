---
name: configurator-sequence
description: Use when the user wants to customise sales order number sequences via the configurator. Triggers on mentions of order numbers, invoice numbers, sequence prefix, increment ID, order prefix.
---

# Sequence Component

Alias: `sequence`

Customises the auto-increment sequence for sales entities (orders, invoices, credit memos, shipments) per store.

## YAML Schema

```yaml
stores:
  <store_code>:
    prefix: <string>
    startValue: <integer>
    suffix: <string>
    step: <integer>
    warningValue: <integer>
    maxValue: <integer>
```

- Root key: `stores`
- Each child key is a store code (e.g. `default`, `usa_en_us`)
- All properties under a store code are optional overrides:
  - `prefix`: string prepended to the increment ID
  - `startValue`: the starting number for the sequence
  - `suffix`: string appended to the increment ID
  - `step`: increment step between each ID
  - `warningValue`: value at which to trigger a warning
  - `maxValue`: maximum allowed value for the sequence

## Sample Configuration

```yaml
stores:
  default:
    prefix: PREFIX_
    startValue: 5000
  usa_en_us:
    prefix: USA_
    startValue: 1000
```

## Notes

- Store codes must match existing Magento store view codes
- Only specify the properties you want to override; omitted properties retain their defaults
- Changes apply to all sales entity sequences (orders, invoices, shipments, credit memos) for the given store
