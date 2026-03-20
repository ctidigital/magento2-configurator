---
name: configurator-catalog-price-rules
description: Use when the user wants to create catalog price rules or promotions via the configurator. Triggers on mentions of catalog price rules, catalog promotions, discount rules, price rules.
---

# Configurator: Catalog Price Rules Component

## Purpose

Creates and manages Magento catalog price rules for automated product discounts. Supports percentage and fixed amount discounts with condition-based targeting.

- **Component alias:** `catalog_price_rules`
- **File format:** YAML
- **Dependencies:** Websites and customer groups must exist (referenced by ID).

## YAML Schema

The file has two root keys: `config` and `rules`.

```yaml
config:
  apply_all: <true|false>    # Whether to apply all rules after processing.

rules:
  <rule_key>:                # Unique identifier key for the rule (internal only).
    name: <string>           # Required. Display name of the rule.
    description: <string>    # Optional. Human-readable description.
    is_active: <0|1>         # Required. 1 = active, 0 = inactive.
    sort_order: <int>        # Optional. Priority/sort order (lower = higher priority).
    website_ids:             # Required. Array of website IDs the rule applies to.
      - <int>
    customer_group_ids:      # Required. Array of customer group IDs the rule applies to.
      - <int>
    from_date: <string>      # Optional. Start date in dd/mm/yyyy format.
    to_date: <string>        # Optional. End date in dd/mm/yyyy format.
    simple_action: <string>  # Required. Discount type (see Discount Types below).
    discount_amount: <int>   # Required. Discount value (0-100 for percentages).
    stop_rules_processing: <0|1>  # Optional. 1 = stop processing lower-priority rules.
    conditions_serialized: <string>  # Required. JSON string defining which products match.
    actions_serialized: <string>     # Required. JSON string defining the action collection.
```

### Discount Types (`simple_action`)

| Value | Description |
|-------|-------------|
| `by_fixed` | Reduce price by a fixed amount. |
| `by_percent` | Reduce price by a percentage. |
| `to_fixed` | Set price to a fixed amount. |
| `to_percent` | Set price to a percentage of the original. |

### Common Customer Group IDs

| ID | Group |
|----|-------|
| `0` | NOT LOGGED IN |
| `1` | General |
| `2` | Wholesale |
| `3` | Retailer |

### Date Format

Dates use `dd/mm/yyyy` format (e.g., `11/10/2021` means October 11, 2021). Leave empty or omit for no date restriction.

### Conditions and Actions (JSON Serialized)

The `conditions_serialized` and `actions_serialized` fields are JSON strings that define the rule's product matching logic and action collection. These follow Magento's internal rule condition format.

#### Conditions structure

```json
{
  "type": "Magento\\CatalogRule\\Model\\Rule\\Condition\\Combine",
  "attribute": null,
  "operator": null,
  "value": "1",
  "is_value_processed": null,
  "aggregator": "all",
  "conditions": [
    {
      "type": "Magento\\CatalogRule\\Model\\Rule\\Condition\\Product",
      "attribute": "<attribute_code>",
      "operator": "<operator>",
      "value": "<value>",
      "is_value_processed": false
    }
  ]
}
```

Common operators: `==` (is), `!=` (is not), `>=`, `<=`, `>`, `<`, `{}` (contains), `!{}` (does not contain), `()` (is one of), `!()` (is not one of).

Common attributes for conditions: `sku`, `category_ids`, `attribute_set_id`, `type_id`, plus any custom product attribute.

#### Actions structure

The actions collection is typically static:

```json
{
  "type": "Magento\\CatalogRule\\Model\\Rule\\Action\\Collection",
  "attribute": null,
  "operator": "=",
  "value": null
}
```

## Complete Example

```yaml
config:
  apply_all: true
rules:
  summer_sale:
    name: Summer Sale 20% Off
    description: 20% discount on all summer products
    is_active: 1
    sort_order: 100
    website_ids:
      - 1
    customer_group_ids:
      - 0
      - 1
      - 2
    from_date: 01/06/2025
    to_date: 31/08/2025
    conditions_serialized: '{"type":"Magento\\CatalogRule\\Model\\Rule\\Condition\\Combine","attribute":null,"operator":null,"value":"1","is_value_processed":null,"aggregator":"all","conditions":[{"type":"Magento\\CatalogRule\\Model\\Rule\\Condition\\Product","attribute":"category_ids","operator":"==","value":"10","is_value_processed":false}]}'
    actions_serialized: '{"type":"Magento\\CatalogRule\\Model\\Rule\\Action\\Collection","attribute":null,"operator":"=","value":null}'
    simple_action: by_percent
    discount_amount: 20
    stop_rules_processing: 0
  clearance_fixed:
    name: Clearance Fixed Discount
    description: Fixed price reduction on clearance SKUs
    is_active: 1
    sort_order: 50
    website_ids:
      - 1
    customer_group_ids:
      - 1
      - 2
    from_date:
    to_date:
    conditions_serialized: '{"type":"Magento\\CatalogRule\\Model\\Rule\\Condition\\Combine","attribute":null,"operator":null,"value":"1","is_value_processed":null,"aggregator":"all","conditions":[{"type":"Magento\\CatalogRule\\Model\\Rule\\Condition\\Product","attribute":"sku","operator":"()","value":"CLR-001,CLR-002,CLR-003","is_value_processed":false}]}'
    actions_serialized: '{"type":"Magento\\CatalogRule\\Model\\Rule\\Action\\Collection","attribute":null,"operator":"=","value":null}'
    simple_action: by_fixed
    discount_amount: 15
    stop_rules_processing: 1
```

## Instructions for Claude

### Creating a new catalog price rules YAML file

1. Create a `.yaml` file (e.g., `configurator/catalog_price_rules.yaml`).
2. Add the `config:` block with `apply_all: true` (recommended to reindex prices after processing).
3. Add the `rules:` block with a unique key per rule.
4. For each rule, set `name`, `is_active`, `website_ids`, `customer_group_ids`, `simple_action`, and `discount_amount`.
5. Build the `conditions_serialized` JSON string to target the desired products.
6. Use the standard `actions_serialized` JSON string (it is typically the same for all catalog price rules).
7. Wrap JSON strings in single quotes in YAML to avoid escaping issues.

### Building conditions_serialized

1. Start with the outer `Combine` condition with `aggregator: "all"` (AND logic) or `aggregator: "any"` (OR logic).
2. Add individual product conditions in the `conditions` array.
3. Use the appropriate `attribute`, `operator`, and `value` for your targeting.
4. Double-escape backslashes in the JSON string within YAML (use `\\\\` for namespace separators, which becomes `\\` in the parsed JSON).

### Registering in master.yaml

```yaml
catalog_price_rules:
  enabled: 1
  method: code
  sources:
    - ../configurator/catalog_price_rules.yaml
```

Place after `websites` (for website IDs) and any components that create the customer groups referenced in `customer_group_ids`.

## Processing Behavior

- The `config` block controls post-processing behavior (`apply_all` triggers rule application/reindex).
- Rules are processed by the `CatalogPriceRulesProcessor` class, which handles creation and updates.
- The rule key (e.g., `summer_sale`) is an internal identifier in the YAML only; Magento identifies rules by name.

## Common Mistakes

1. **Malformed JSON in serialized fields.** The `conditions_serialized` and `actions_serialized` values must be valid JSON strings. Test with a JSON validator before adding to YAML.
2. **Wrong date format.** Use `dd/mm/yyyy`, not `yyyy-mm-dd` or `mm/dd/yyyy`.
3. **Forgetting to wrap JSON in quotes.** The JSON strings must be wrapped in single quotes in YAML to prevent YAML from interpreting braces and colons.
4. **Invalid `simple_action` value.** Must be one of: `by_fixed`, `by_percent`, `to_percent`, `to_fixed`.
5. **Discount amount out of range for percentages.** When using `by_percent` or `to_percent`, the `discount_amount` should be between 0 and 100.
6. **Missing `website_ids` or `customer_group_ids`.** Both are required arrays. Omitting them means the rule will not apply to any website or customer group.
7. **Forgetting `config.apply_all`.** Without `apply_all: true`, catalog price rule changes may not take effect until the next manual reindex.
