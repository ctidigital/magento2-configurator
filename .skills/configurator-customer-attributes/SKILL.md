---
name: configurator-customer-attributes
description: Use when the user wants to create or modify customer EAV attributes via the configurator. Triggers on mentions of customer attributes, customer custom fields, customer EAV.
---

# Customer Attributes Component

- **Alias**: `customer_attributes`
- **Format**: YAML
- **Source file**: `Component/CustomerAttributes.php` (extends `Component/Attributes.php`)
- **Sample**: `Samples/Components/CustomerAttributes/customer_attributes.yaml`
- **Entity type**: `customer`
- **Dependencies**: None

## Schema

The YAML file has a top-level `customer_attributes` key. Each child key is the attribute code, and its value is a map of attribute configuration.

```yaml
customer_attributes:
  <attribute_code>:
    label: <frontend label>
    input: <frontend input type>
    # ... additional configuration
```

### Core Fields

| Field      | Type   | Required | Description                                         |
|------------|--------|----------|-----------------------------------------------------|
| `label`    | string | yes      | Frontend label displayed in forms and admin          |
| `input`    | string | yes      | Input type: `text`, `select`, `boolean`, `textarea`, `date`, `multiselect` |
| `visible`  | int    | no       | Whether attribute is visible (1 = yes, 0 = no)      |
| `position` | int    | no       | Sort order/position in forms                        |
| `required` | int    | no       | Whether attribute is required (1 = yes, 0 = no)     |
| `unique`   | int    | no       | Whether value must be unique (1 = yes, 0 = no)      |
| `default`  | mixed  | no       | Default value for the attribute                     |
| `system`   | int    | no       | Whether this is a system attribute (1 = yes, 0 = no)|

### used_in_forms

Controls which Magento forms display the attribute. If omitted, defaults to: `customer_account_create`, `customer_account_edit`, `adminhtml_checkout`, `adminhtml_customer`.

Specify as a comma-separated string or under a `values` key:

```yaml
used_in_forms: customer_account_create,customer_account_edit
```

Available form codes:
- `customer_account_create` -- storefront registration
- `customer_account_edit` -- storefront account edit
- `adminhtml_checkout` -- admin checkout
- `adminhtml_customer` -- admin customer edit
- `checkout_register` -- checkout registration

### Dropdown/Multiselect Options

For `select` or `multiselect` inputs, provide options:

```yaml
option:
  values:
    - Option 1
    - Option 2
    - Option 3
```

### Attribute Config Mapping

The component maps short YAML keys to Magento's internal attribute properties. In addition to the base `Attributes` mappings, customer attributes add:

| YAML key   | Magento property |
|------------|------------------|
| `visible`  | `is_visible`     |
| `position` | `sort_order`     |
| `system`   | `is_system`      |

Base mappings inherited from `Attributes` include: `label` -> `frontend_label`, `type` -> `backend_type`, `input` -> `frontend_input`, `required` -> `is_required`, `source` -> `source_model`, `backend` -> `backend_model`, `frontend` -> `frontend_model`, `unique` -> `is_unique`, `default` -> `default_value`.

## Behavior

- If an attribute with the same code already exists, it is updated (not duplicated).
- New attributes are automatically assigned to the default attribute set (ID 1) and default attribute group (ID 1).
- The `used_in_forms` data is saved via the customer attribute resource model after initial EAV setup.

## Example

```yaml
customer_attributes:
  loyalty_number:
    label: Loyalty Number
    input: text
    visible: 1
    required: 0
    position: 10
    used_in_forms: customer_account_create,customer_account_edit

  membership_tier:
    label: Membership Tier
    input: select
    visible: 1
    position: 20
    used_in_forms: customer_account_edit,adminhtml_customer
    option:
      values:
        - Bronze
        - Silver
        - Gold
        - Platinum

  accepts_marketing:
    label: Accepts Marketing
    input: boolean
    visible: 1
    position: 30
    used_in_forms: checkout_register,customer_account_create,customer_account_edit,adminhtml_checkout
```

## Configurator YAML Reference

```yaml
components:
  customer_attributes:
    sources:
      - customer_attributes.yaml
```
