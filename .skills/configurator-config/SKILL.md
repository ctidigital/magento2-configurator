---
name: configurator-config
description: Use when the user wants to set Magento system configuration values (core_config_data) via the configurator. Triggers on mentions of system config, settings, config paths, store configuration, core_config_data.
---

# Configurator Config Component

## Purpose

Sets Magento system configuration values (`core_config_data`) at global, website, or store view scope. This component manages all settings found in the Magento admin under Stores > Configuration.

## Component Alias

`config`

## File Format

YAML

## Schema

Configuration values are organized by scope. A single file can contain one, two, or all three scope levels. You can also split scopes across multiple source files.

```yaml
global:                          # Optional. Global (default) scope settings.
  -
    path: <string>               # Required. The system config path (e.g., "general/country/default").
    value: <string|int|float>    # Required. The value to set.
    encryption: <0|1>            # Optional. Set to 1 to encrypt the value before saving.

websites:                        # Optional. Website-scoped settings.
  <website_code>:                # The website code (must already exist).
    -
      path: <string>             # Required. The system config path.
      value: <string|int|float>  # Required. The value to set.
      encryption: <0|1>          # Optional. Set to 1 to encrypt the value before saving.

stores:                          # Optional. Store-view-scoped settings.
  <store_view_code>:             # The store view code (must already exist).
    -
      path: <string>             # Required. The system config path.
      value: <string|int|float>  # Required. The value to set.
      encryption: <0|1>          # Optional. Set to 1 to encrypt the value before saving.
```

### Field Details

| Field | Required | Description |
|-------|----------|-------------|
| `path` | Yes | A Magento config path in the format `section/group/field` (e.g., `general/country/default`). This corresponds to the path column in the `core_config_data` table. |
| `value` | Yes | The value to store. For the special path `design/theme/theme_id`, you can pass the theme path string (e.g., `frontend/Vendor/theme`) and the component will automatically resolve it to the numeric theme ID. |
| `encryption` | No | Set to `1` to encrypt the value before saving. Used for sensitive data such as API keys and passwords. The component also auto-detects encryption for paths that use the `Magento\Config\Model\Config\Backend\Encrypted` backend model. |

### Scope Hierarchy

Magento config values cascade: global is the base, website scope overrides global, and store scope overrides website. Only set values at the narrowest scope needed.

| Scope | YAML key | Identified by |
|-------|----------|---------------|
| Global (default) | `global` | No identifier needed; applies to scope ID 0. |
| Website | `websites.<website_code>` | Website code (e.g., `base`, `usa`). |
| Store View | `stores.<store_view_code>` | Store view code (e.g., `default`, `usa_en_us`). |

## Complete Example

```yaml
# File: configurator/Configuration/global.yaml
global:
  -
    path: general/country/default
    value: US
  -
    path: carriers/tablerate/active
    value: 1
  -
    path: carriers/tablerate/condition_name
    value: package_value
  -
    path: payment/gateway/api_key
    value: sk_live_abc123
    encryption: 1
```

```yaml
# File: configurator/Configuration/base-website-config.yaml
websites:
  base:
    -
      path: general/country/default
      value: GB
stores:
  default:
    -
      path: general/locale/code
      value: en_GB
```

```yaml
# File: configurator/Configuration/theme.yaml
global:
  -
    path: design/theme/theme_id
    value: frontend/Vendor/theme
```

For the `design/theme/theme_id` path, passing a theme path string (instead of a numeric ID) causes the component to look up the theme by its full path and resolve it to the correct ID.

## Dependencies

- **websites**: Website codes and store view codes referenced under `websites:` and `stores:` scopes must already exist. Ensure the `websites` component runs before `config` in master.yaml.

## Instructions for Claude

### Finding valid config paths

Config paths follow the pattern `section/group/field` and correspond to Magento admin fields under Stores > Configuration. Common paths include:

- `general/country/default` - Default country
- `general/locale/code` - Locale code
- `general/locale/timezone` - Timezone
- `web/unsecure/base_url` - Base URL (unsecure)
- `web/secure/base_url` - Base URL (secure)
- `web/secure/use_in_frontend` - Use HTTPS on frontend
- `design/theme/theme_id` - Theme (accepts theme path or numeric ID)
- `trans_email/ident_general/email` - General contact email
- `carriers/flatrate/active` - Flat rate shipping enable/disable
- `payment/*/active` - Payment method enable/disable
- `catalog/seo/product_url_suffix` - Product URL suffix
- `tax/calculation/algorithm` - Tax calculation algorithm

To discover additional paths, check the `system.xml` files in Magento modules or query the `core_config_data` table.

### Creating a new config file

1. Create a `.yaml` file (e.g., `configurator/Configuration/custom.yaml`).
2. Use `global:`, `websites:`, or `stores:` as top-level keys depending on the scope.
3. Under each scope, provide an array of `path`/`value` pairs.
4. Add `encryption: 1` for any sensitive values (passwords, API keys, secrets).

### Amending an existing config file

1. Read the existing file first.
2. To add new settings, append entries to the relevant scope array.
3. To change a value, update the `value` field for the matching `path`.
4. The processor compares values and only saves when differences are detected, so re-running with the same values is safe.

### Registering in master.yaml

Add the `config` component to `master.yaml` after `websites`:

```yaml
config:
  enabled: 1
  method: code
  sources:
    - ../configurator/Configuration/global.yaml
    - ../configurator/Configuration/base-website-config.yaml
```

Multiple source files are merged in order. You can split configuration by scope, environment, or logical grouping.

## Processing Behavior

- The processor validates that the scope key is one of `global`, `websites`, or `stores`. Invalid scopes raise a `ComponentException`.
- For `websites` scope, the processor loads the website by code and uses its ID. If the website code does not exist, an error is logged.
- For `stores` scope, the processor loads the store view by code and uses its ID. If the store view code does not exist, an error is logged.
- Existing values are compared before saving. If the value is already set correctly, the save is skipped (logged as "Already").
- Encryption is applied when `encryption: 1` is set or when the config path uses the `Magento\Config\Model\Config\Backend\Encrypted` backend model (auto-detected from Magento's initial config metadata).

## Common Mistakes

1. **Wrong scope level**: Setting a store-view-specific value under `global` instead of `stores`, or vice versa. This causes the value to apply at the wrong scope. Check the Magento admin to see at which scope a setting is configurable.
2. **Invalid config paths**: Typos in config paths will silently save a value that Magento never reads. Verify paths against `system.xml` or the `core_config_data` table.
3. **Forgetting `encryption: 1` for sensitive values**: Passwords, API keys, and secrets should always have `encryption: 1`. While auto-detection works for paths with `Encrypted` backend models, explicitly setting the flag is safer for custom or third-party module paths.
4. **Referencing nonexistent website or store codes**: The website code under `websites:` and the store view code under `stores:` must already exist in Magento. If they do not, the processor logs an error and skips those entries.
5. **Confusing website codes with store view codes**: Under `stores:` you must use the **store view code** (e.g., `default`, `usa_en_us`), not the website code or store group name.
6. **Using numeric theme IDs directly**: While numeric IDs work for `design/theme/theme_id`, using the theme path string (e.g., `frontend/Vendor/theme`) is more portable and readable. The component resolves the ID automatically.
