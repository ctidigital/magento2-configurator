---
name: configurator-websites
description: Use when the user wants to create or modify Magento website, store group, or store view definitions for the configurator. Triggers on mentions of websites, stores, store views, store groups, multi-store setup.
---

# Configurator Websites Component

## Purpose

Configures Magento websites, store groups, and store views. This is the foundational component that establishes the multi-store hierarchy. Most other components depend on the website/store structure created here.

## Component Alias

`websites`

## File Format

YAML

## Schema

The top-level key is `websites`. Each child key is a **website code** (unique identifier, lowercase, underscores allowed).

```yaml
websites:
  <website_code>:
    name: <string>               # Required. Display name of the website.
    store_groups:                 # Required. Array of store groups under this website.
      -
        group_id: <int>          # Optional. Use only when updating an existing store group by ID.
        name: <string>           # Required. Display name of the store group.
        code: <string>           # Optional. Store group code. If omitted, the group is loaded by name.
        root_category_id: <int>  # Required. ID of the root category assigned to this store group.
        default_store: <string>  # Required. Code of the store view to use as default for this group.
        store_views:             # Required. Map of store view code => store view data.
          <store_view_code>:
            name: <string>       # Required. Display name of the store view.
            is_active: <0|1>     # Required. Whether the store view is active (1) or disabled (0).
```

### Field Details

#### Website level

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Human-readable website name shown in the admin panel. |
| `store_groups` | Yes | Array of store group definitions nested under the website. |

The website code (the YAML key) must be unique across the Magento instance. The default Magento website uses code `base`.

#### Store Group level

| Field | Required | Description |
|-------|----------|-------------|
| `group_id` | No | Numeric ID of an existing store group. Use when you need to target a specific group for updates. If omitted, the group is looked up by `name`. |
| `name` | Yes | Display name of the store group. Also used for lookup when `group_id` is absent. |
| `code` | No | Store group code. Optional identifier. |
| `root_category_id` | Yes | The ID of the root category. Magento's default root category has ID `2`. |
| `default_store` | Yes | The store view code that will be the default for this group. Must match one of the codes defined in `store_views`. |

#### Store View level

The store view code is the YAML map key (e.g., `default`, `usa_en_us`). It must be unique across the Magento instance.

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Display name of the store view. |
| `is_active` | Yes | `1` to enable, `0` to disable the store view. |

## Complete Example

```yaml
websites:
  base:
    name: Main Website UK
    store_groups:
      -
        group_id: 1
        name: Main Website Store
        root_category_id: 2
        default_store: default
        store_views:
          default:
            name: UK Store View
            is_active: 1
  usa:
    name: Website US
    store_groups:
      -
        name: US Store
        code: usa_en
        root_category_id: 2
        default_store: usa_en_us
        store_views:
          usa_en_us:
            name: USA Store View
            is_active: 1
          usa_es_us:
            name: USA Spanish Store View
            is_active: 1
```

## Dependencies

None. The `websites` component should run first in the master.yaml sequence because most other components (config, categories, products, etc.) depend on websites and store views existing.

## Instructions for Claude

### Creating a new websites YAML file

1. Create a `.yaml` file (e.g., `configurator/websites.yaml`) with the `websites:` root key.
2. Define each website as a child key using a unique website code.
3. Nest `store_groups` as an array under each website.
4. Nest `store_views` as a map under each store group.
5. Ensure `default_store` in each store group references a store view code defined within that same group.

### Amending an existing websites file

1. Read the existing file first.
2. To add a new website, add a new key under `websites:`.
3. To add a new store view, add a new entry under the relevant `store_views:` map.
4. To modify an existing entry, change the relevant field values. The processor compares values and only saves when differences are detected.

### Registering in master.yaml

Add the `websites` component to `master.yaml`:

```yaml
websites:
  enabled: 1
  method: code
  sources:
    - ../configurator/websites.yaml
```

Place `websites` before any components that depend on store codes (e.g., `config`, `categories`, `products`).

## Processing Behavior

- The processor creates or updates websites, store groups, and store views idempotently.
- Existing entities are loaded by code (websites, store views) or by name/group_id (store groups).
- Only changed fields trigger a save operation.
- After creating new websites, store groups, or store views, a `catalog_product_price` reindex is triggered automatically.
- The `store_add` event is dispatched when a store view is created or updated.

## Common Mistakes

1. **Forgetting `root_category_id`**: Every store group requires a `root_category_id`. Magento's default root category ID is `2`. If you have created custom root categories, use their IDs.
2. **Duplicate store view codes**: Store view codes must be globally unique across the entire Magento instance, not just within a website.
3. **Referencing store views before they exist**: The `default_store` value must reference a store view code that is defined within the same store group's `store_views` map. The processor creates store views before setting the default, so this will work as long as the code is present in the YAML.
4. **Using `group_id` on new store groups**: Only use `group_id` when updating an existing store group. For new groups, omit it and let Magento assign the ID.
5. **Forgetting `is_active`**: If `is_active` is omitted or set to `0`, the store view will be disabled and inaccessible on the frontend.
6. **Website code format**: Website codes should be lowercase alphanumeric with underscores. Do not use hyphens, spaces, or uppercase characters.
