---
name: configurator-customer-groups
description: Use when the user wants to create or modify Magento customer groups via the configurator. Triggers on mentions of customer groups, customer tax classes.
---

# Customer Groups Component

- **Alias**: `customergroups`
- **Format**: YAML
- **Source file**: `Component/CustomerGroups.php`
- **Sample**: `Samples/Components/CustomerGroups/customergroups.yaml`
- **Dependencies**: None (tax classes must already exist in the database)

## Schema

The YAML file has a top-level `customergroups` key containing an array of entries. Each entry specifies a tax class name and an array of groups to create under that tax class.

```yaml
customergroups:
  - taxclass: <tax class name>    # Must match an existing tax class in the database
    groups:
      - name: <group name>        # Max 32 characters
      - name: <another group>
```

### Fields

| Field      | Type   | Required | Description                                           |
|------------|--------|----------|-------------------------------------------------------|
| `taxclass` | string | yes      | Name of an existing Magento tax class (e.g. "Retail Customer") |
| `groups`   | array  | yes      | List of customer groups to create                     |
| `name`     | string | yes      | Customer group code/name, max 32 characters           |

## Behavior

- Groups are matched by `customer_group_code`. If a group with the same name already exists, creation is skipped (idempotent).
- The tax class is resolved by name. If no matching tax class exists, an error is logged and the groups under that tax class are skipped.
- Group names longer than 32 characters cause a validation error for that group.

## Example

```yaml
customergroups:
  - taxclass: Retail Customer
    groups:
      - name: VIP
      - name: Subscriber
  - taxclass: Wholesale Customer
    groups:
      - name: Distributor
```

## Configurator YAML Reference

Register the component in your configurator YAML:

```yaml
components:
  customergroups:
    sources:
      - customergroups.yaml
```
