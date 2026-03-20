---
name: configurator-api-integrations
description: Use when the user wants to create Magento API/OAuth integrations via the configurator. Triggers on mentions of API integrations, OAuth, REST API access, API permissions.
---

# Configurator: API Integrations Component

## Purpose

Creates Magento API/OAuth integrations with assigned resource permissions. Each integration is automatically activated and authorized with an access token upon creation.

- **Component alias:** `apiintegrations`
- **File format:** YAML
- **Dependencies:** None

## YAML Schema

The top-level key is `apiintegrations`. Its value is an array of integration objects.

```yaml
apiintegrations:
  - name: <string>           # Required. Unique integration name.
    email: <string>          # Required. Contact email for the integration.
    setuptype: <0|1>         # Required. 0 = manual, 1 = config-based.
    callbackurl: <string>    # Optional. OAuth callback URL (leave empty for token-based access).
    identityurl: <string>    # Optional. OAuth identity link URL (leave empty for token-based access).
    resources:               # Required. Array of Magento ACL resource IDs to grant.
      - <string>
```

### Field Details

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Unique name for the integration. Used for lookup -- if an integration with this name already exists, creation is skipped. |
| `email` | Yes | Contact email address associated with the integration. |
| `setuptype` | Yes | `0` for manual setup, `1` for config-based setup. |
| `callbackurl` | No | The OAuth callback URL. Leave empty or omit for simple token-based API access. |
| `identityurl` | No | The OAuth identity link URL. Leave empty or omit for simple token-based API access. |
| `resources` | Yes | Array of Magento ACL resource identifiers the integration is granted access to. |

### Common ACL Resources

| Resource ID | Description |
|-------------|-------------|
| `Magento_Backend::admin` | Top-level admin access (usually required) |
| `Magento_Catalog::catalog` | Catalog root |
| `Magento_Catalog::catalog_inventory` | Inventory management |
| `Magento_Catalog::products` | Product management |
| `Magento_Catalog::categories` | Category management |
| `Magento_Catalog::attributes_attributes` | Attribute management |
| `Magento_Catalog::update_attributes` | Mass attribute updates |
| `Magento_Catalog::sets` | Attribute set management |
| `Magento_Sales::sales` | Sales root |
| `Magento_Sales::create` | Create orders |
| `Magento_Sales::actions_view` | View orders |
| `Magento_Sales::actions_edit` | Edit orders |
| `Magento_Sales::cancel` | Cancel orders |
| `Magento_Sales::hold` | Hold orders |
| `Magento_Sales::unhold` | Unhold orders |

## Complete Example

```yaml
apiintegrations:
  - name: API Product Management
    email: apiprodmanagement@email.com
    setuptype: 1
    callbackurl:
    identityurl:
    resources:
      - 'Magento_Backend::admin'
      - 'Magento_Catalog::catalog'
      - 'Magento_Catalog::catalog_inventory'
      - 'Magento_Catalog::products'
      - 'Magento_Catalog::categories'
      - 'Magento_Catalog::attributes_attributes'
      - 'Magento_Catalog::update_attributes'
      - 'Magento_Catalog::sets'
  - name: API Sales Management
    email: apisalesmanagement@email.com
    setuptype: 0
    callbackurl:
    identityurl:
    resources:
      - 'Magento_Backend::admin'
      - 'Magento_Sales::sales'
      - 'Magento_Sales::create'
      - 'Magento_Sales::actions_view'
      - 'Magento_Sales::actions_edit'
      - 'Magento_Sales::cancel'
      - 'Magento_Sales::hold'
      - 'Magento_Sales::unhold'
```

## Instructions for Claude

### Creating a new API integrations YAML file

1. Create a `.yaml` file (e.g., `configurator/apiintegrations.yaml`) with the `apiintegrations:` root key.
2. Add one array entry per integration.
3. Each entry must have `name`, `email`, `setuptype`, and `resources`.
4. For token-based REST API access (most common), leave `callbackurl` and `identityurl` empty and set `setuptype: 0`.
5. Quote resource strings that contain `::` to avoid YAML parsing issues.

### Registering in master.yaml

```yaml
apiintegrations:
  enabled: 1
  method: code
  sources:
    - ../configurator/apiintegrations.yaml
```

## Processing Behavior

- If an integration with the same `name` already exists, creation is skipped entirely (no update).
- New integrations are automatically activated (status = 1) and authorized with an access token.
- Permissions are granted via `AuthorizationService::grantPermissions`.

## Common Mistakes

1. **Missing `name` field.** The name is required and used as the unique identifier. Entries without a name are skipped with an error.
2. **Forgetting `Magento_Backend::admin` in resources.** Most API access requires this top-level resource as a prerequisite.
3. **Not quoting resource strings.** Resource IDs like `Magento_Catalog::products` contain `::` which can confuse YAML parsers. Always wrap them in single quotes.
4. **Expecting updates on re-run.** The component only creates new integrations. If an integration with the same name exists, it is skipped. To change permissions, delete the integration in the admin first.
