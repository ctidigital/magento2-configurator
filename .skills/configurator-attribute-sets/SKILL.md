---
name: configurator-attribute-sets
description: Use when the user wants to create or modify Magento attribute sets and attribute groups via the configurator. Triggers on mentions of attribute sets, attribute groups, assigning attributes to groups.
---

# Configurator: Attribute Sets

## Purpose

Creates and maintains Magento product attribute sets with attribute groups and assigns attributes to those groups via YAML configuration.

- **Component alias:** `attribute_sets`
- **File format:** YAML
- **Entity type:** `catalog_product`
- **Dependencies:** `attributes` -- all attribute codes referenced in groups must already exist in Magento. The `attributes` component must run before `attribute_sets`.

## YAML Schema

```yaml
attribute_sets:
-
  name: <string>                  # REQUIRED - attribute set name
  inherit: <string>               # Optional - copy groups/attributes from an existing set
  groups:                          # List of attribute groups
    -
      name: <string>              # REQUIRED - group display name
      code: <string>              # Optional - group code (auto-generated from name if omitted)
      attributes:                  # List of attribute codes to assign to this group
        - <attribute_code>
        - <attribute_code>
```

### Field Details

| Field              | Required | Description                                                                                     |
|--------------------|----------|-------------------------------------------------------------------------------------------------|
| `name`             | Yes      | The attribute set name. If it already exists, it will be updated.                               |
| `inherit`          | No       | Name of an existing attribute set to use as a skeleton. Copies all groups and attribute assignments from that set before applying the `groups` config. Commonly set to `Default`. |
| `groups`           | No       | Array of attribute groups. If omitted, only the set is created (or inherited).                  |
| `groups[].name`    | Yes      | Display name of the attribute group (e.g. "General", "Prices", "Design").                      |
| `groups[].code`    | No       | Internal group code. If omitted, auto-generated from `name` using Magento's `convertToAttributeGroupCode` (lowercased, hyphenated). Specify explicitly when Magento's auto-code does not match what you expect. |
| `groups[].attributes` | Yes   | List of attribute codes to assign to this group. Each code must reference an existing attribute. |

## Complete Example

```yaml
attribute_sets:
-
  name: Default
  inherit: Default
  groups:
    -
      name: General
      code: general
      attributes:
        - color
-
  name: Shirts
  inherit: Default
  groups:
    -
      name: General
      code: general
      attributes:
        - colour
        - color
    -
      name: Prices
      code: prices
      attributes:
        - rrp
        - test_attr
-
  name: Example Attribute Set 2
  inherit: Default
  groups:
    -
      name: Prices
      attributes:
        - rrp
-
  name: Standalone Set
  groups:
    -
      name: Prices
      attributes:
        - rrp
```

### Inheriting vs not inheriting

- **With `inherit`:** The new set starts as a copy of the named set (all its groups and attribute assignments). Then the `groups` block adds or updates groups and assigns additional attributes on top.
- **Without `inherit`:** The set is created empty. Only the groups and attributes explicitly listed in `groups` will exist. This is less common; most sets inherit from `Default` to get the standard Magento groups (General, Prices, Images, Meta Information, etc.).

## Instructions for Claude

### Creating a new attribute set

1. Choose a descriptive `name` for the set.
2. Almost always set `inherit: Default` so the set starts with Magento's standard groups and system attributes.
3. Define any additional or custom groups under `groups`, listing the attribute codes that belong in each group.
4. Make sure every attribute code listed has already been created via the `attributes` component.

### Adding attributes to an existing set

Add a new entry (or update the existing entry) with the set's `name`. List the group and the attribute codes to assign. The component will create the group if it does not exist, or add attributes to an existing group.

### Creating custom groups

Simply list a group with a `name` that does not already exist on the set. The component creates it automatically. Optionally specify `code` if you need a specific internal code.

## Common Mistakes

1. **Inheriting from a non-existent set.** The `inherit` value must exactly match an existing attribute set name in Magento. If the named set does not exist, the component throws `ComponentException: Could not find attribute set name.`
2. **Referencing non-existent attribute codes.** Every code in `groups[].attributes` must already exist as a product attribute. If it does not, the component throws `ComponentException: Attribute '<code>' does not exist.` Run the `attributes` component first.
3. **Omitting `inherit` unintentionally.** Without `inherit`, the set has no default groups. System attributes like `name`, `sku`, `price` will not be in the set unless explicitly assigned. This usually causes problems. Use `inherit: Default` unless you have a specific reason not to.
4. **Mismatched group codes.** If a group already exists on the set with a different code than what you specify, you may get a `DuplicateException`. Either omit `code` to let Magento auto-generate it, or check the existing group code first.
5. **YAML list format.** Each attribute set entry starts with `-` at the same indentation level. Each group also starts with `-`. Incorrect indentation causes parse errors.
