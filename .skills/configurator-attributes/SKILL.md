---
name: configurator-attributes
description: Use when the user wants to create or modify Magento product EAV attributes via the configurator. Triggers on mentions of product attributes, dropdowns, swatches, select attributes, attribute options, image roles, EAV attributes.
---

# Configurator: Product EAV Attributes

## Purpose

Creates and maintains Magento product EAV attributes via YAML configuration. Handles creation of new attributes, updating existing attributes, managing option values, visual/text swatches, per-store option labels, and image role attributes.

- **Component alias:** `attributes`
- **File format:** YAML
- **Entity type:** `catalog_product`
- **Dependencies:** None. However, this component MUST run before `attribute_sets` and `products` because both reference attribute codes created here.

## attributeConfigMap (Shorthand to Magento Internal Names)

The component accepts shorthand YAML keys and maps them to Magento's internal attribute property names. You can use EITHER the shorthand OR the full Magento name in your YAML:

| Shorthand (YAML key)          | Magento Internal Name              |
|-------------------------------|-------------------------------------|
| `label`                       | `frontend_label`                    |
| `type`                        | `backend_type`                      |
| `input`                       | `frontend_input`                    |
| `product_types`               | `apply_to`                          |
| `required`                    | `is_required`                       |
| `source`                      | `source_model`                      |
| `backend`                     | `backend_model`                     |
| `frontend`                    | `frontend_model`                    |
| `searchable`                  | `is_searchable`                     |
| `global`                      | `is_global`                         |
| `filterable_in_search`        | `is_filterable_in_search`           |
| `unique`                      | `is_unique`                         |
| `visible_in_advanced_search`  | `is_visible_in_advanced_search`     |
| `comparable`                  | `is_comparable`                     |
| `visible_on_front`            | `is_visible_on_front`              |
| `filterable`                  | `is_filterable`                     |
| `user_defined`                | `is_user_defined`                   |
| `default`                     | `default_value`                     |
| `used_for_promo_rules`        | `is_used_for_promo_rules`           |

Properties NOT in this map (e.g. `used_in_product_listing`, `position`) are passed through as-is.

`user_defined` defaults to `1` if not specified.

## Full YAML Schema

```yaml
attributes:
  <attribute_code>:                    # string, snake_case, unique identifier
    # --- Display ---
    label: <string>                    # REQUIRED - frontend label (shorthand for frontend_label)
    global: <0|1|2>                    # Scope: 0=store, 1=global, 2=website

    # --- Backend/Frontend Type ---
    type: <string>                     # Backend type: varchar, int, decimal, text, datetime, static
    input: <string>                    # Frontend input type (see Input Types below)

    # --- Behaviour ---
    required: <0|1>                    # Is required (default: 0)
    unique: <0|1>                      # Must be unique
    default: <value>                   # Default value
    user_defined: <0|1>               # User defined (default: 1)

    # --- Search & Filter ---
    searchable: <0|1>                  # Searchable in frontend
    filterable: <0|1>                  # Filterable in layered navigation
    filterable_in_search: <0|1>        # Filterable in search results
    visible_in_advanced_search: <0|1>  # Show in advanced search
    comparable: <0|1>                  # Available in product compare

    # --- Display/Listing ---
    visible_on_front: <0|1>            # Visible on product detail page
    used_in_product_listing: <0|1>     # Available in product listing
    used_for_promo_rules: <0|1>        # Available for promo rules
    position: <int>                    # Sort order

    # --- Product Types ---
    product_types:                     # Which product types this applies to
      - simple
      - configurable
      - bundle
      - grouped
      - virtual
      - downloadable

    # --- Models (advanced) ---
    source: <class>                    # Source model class
    backend: <class>                   # Backend model class
    frontend: <class>                  # Frontend model class

    # --- Options (for select/multiselect/swatch types) ---
    option:
      values:                          # See Options Handling below
        - Option1
        - Option2
      store_labels:                    # Optional per-store labels
        <store_code>:
          Option1: Display Label 1
          Option2: Display Label 2
```

## Attribute Input Types

| `input` value    | `type` value | Description                                |
|------------------|--------------|--------------------------------------------|
| `text`           | `varchar`    | Single-line text input                     |
| `textarea`       | `text`       | Multi-line text input                      |
| `date`           | `datetime`   | Date picker                                |
| `boolean`        | `int`        | Yes/No dropdown                            |
| `select`         | `int`        | Dropdown with options                      |
| `multiselect`    | `varchar`    | Multi-select with options                  |
| `price`          | `decimal`    | Price field                                |
| `media_image`    | `varchar`    | Image role attribute (e.g. hover image)    |
| `swatch_visual`  | `int`        | Visual swatch (hex colors or images)       |
| `swatch_text`    | `int`        | Text-based swatch labels                   |

## Options Handling

### Simple select/multiselect options

Plain list of string values:

```yaml
option:
  values:
    - Red
    - Green
    - Blue
```

### Visual swatch options (`input: swatch_visual`)

Key-value pairs where the key is the label and the value is the hex colour code:

```yaml
option:
  values:
    'Black': '#000000'
    'White': '#FFFFFF'
    'Navy Blue': '#000080'
```

### Text swatch options (`input: swatch_text`)

Same format as simple select -- the option label IS the swatch text:

```yaml
option:
  values:
    - Small
    - Medium
    - Large
```

### Per-store option labels (`option.store_labels`)

Maps admin option values to per-store display labels. The admin values act as internal codes; each store view can show its own human-readable label. Use the store code (from `websites.yaml`) or a numeric store ID.

```yaml
option:
  values:
    - XS
    - S
    - M
    - L
  store_labels:
    default:
      XS: Extra Small
      S: Small
      M: Medium
      L: Large
    usa_en_us:
      XS: Extra Small
      S: Small
      M: Medium
      L: Large
```

The `store_labels` block is optional and fully backward compatible.

## Complete Examples

### Simple select attribute

```yaml
attributes:
  test_attr:
    global: 1
    label: Test Attribute
    type: int
    input: select
    visible_on_front: 1
    filterable: 1
    searchable: 1
    visible_in_advanced_search: 0
    product_types:
      - simple
    option:
      values:
        - Red
        - Green
        - Yellow
        - Blue
```

### Visual swatch attribute with hex colours

```yaml
attributes:
  colour_with_swatches:
    global: 1
    label: Colour
    type: int
    input: swatch_visual
    visible_on_front: 1
    filterable: 1
    filterable_in_search: 1
    required: 0
    searchable: 1
    visible_in_advanced_search: 1
    used_for_promo_rules: 0
    used_in_product_listing: 1
    position: 20
    product_types:
      - simple
      - configurable
    option:
      values:
        'Black': '#000000'
        'White': '#FFFFFF'
        'Red': '#FF0000'
        'Navy Blue': '#000080'
```

### Attribute with per-store option labels

```yaml
attributes:
  shirt_size:
    global: 1
    label: Shirt Size
    type: int
    input: select
    visible_on_front: 1
    filterable: 1
    required: 0
    searchable: 1
    visible_in_advanced_search: 0
    used_in_product_listing: 1
    product_types:
      - simple
      - configurable
    option:
      values:
        - XS
        - S
        - M
        - L
        - XL
      store_labels:
        default:
          XS: Extra Small
          S: Small
          M: Medium
          L: Large
          XL: Extra Large
```

### Price attribute (using full Magento property names)

```yaml
attributes:
  rrp:
    frontend_label: Recommended Retail Price
    frontend_input: price
    used_in_product_listing: 1
    product_types:
      - bundle
      - simple
```

### Image role attribute (e.g. hover image)

```yaml
attributes:
  hover_image:
    label: Hover Image
    type: varchar
    input: media_image
    required: 0
    global: 1
    visible_on_front: 0
    used_in_product_listing: 1
    user_defined: 1
    product_types:
      - simple
      - configurable
```

This creates a new image role in Magento. After creation, the attribute appears as an assignable image role on product edit pages (alongside base_image, small_image, thumbnail, swatch_image). Products can then assign an image to this role either manually or via the products configurator component.

## Instructions for Claude

### Creating a new attribute

1. Pick a unique `attribute_code` in snake_case. It must not conflict with existing Magento or custom attributes.
2. Set `label` (required) and choose the correct `type`/`input` combination from the Input Types table.
3. For select/multiselect attributes, provide `option.values`.
4. For swatch attributes, set `input: swatch_visual` or `input: swatch_text` and format option values accordingly.
5. Set `product_types` to restrict which product types can use this attribute.
6. Add `used_in_product_listing: 1` if the attribute value needs to appear on category/listing pages.
7. Place the YAML under the top-level `attributes:` key.

### Adding options to an existing attribute

The component compares existing options against the YAML. New options in the YAML that do not exist on the attribute are added. Existing options are preserved (not removed). Simply add the new values to the `option.values` list.

### Creating an image role attribute

Set `input: media_image` and `type: varchar`. This registers a new image role in Magento that can be assigned to product images. Common use case: hover images for product listings.

## Common Mistakes

1. **Wrong type/input combination.** Always match `type` to `input` per the Input Types table. A `select` must have `type: int`, a `price` must have `type: decimal` (or omit `type` and let Magento default it).
2. **Missing option values.** Select, multiselect, and swatch attributes require an `option.values` block. Without it the attribute is created with no options.
3. **Duplicate attribute codes.** Attribute codes are global. Check for conflicts with existing Magento core attributes (e.g. `color`, `size`, `weight`).
4. **Using `label` vs `frontend_label` inconsistently.** Both work, but use one style consistently within a file. The shorthand `label` is preferred.
5. **Visual swatch values without hex format.** For `swatch_visual`, each option value must be a valid hex colour string (e.g. `'#FF0000'`), not a plain label.
6. **Forgetting `product_types`.** If omitted, the attribute applies to all product types. Be explicit when the attribute should only appear on specific types.
7. **store_labels keys not matching option values.** The keys inside each store's label map must exactly match the admin values listed in `option.values`.
