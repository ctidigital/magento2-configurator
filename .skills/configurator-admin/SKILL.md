---
name: configurator-admin
description: Use when the user wants to create or modify Magento admin roles, admin users, or their permissions via the configurator. Triggers on mentions of admin roles, admin users, ACL permissions, backend users, API roles.
---

# Admin Roles and Admin Users Components

Two components manage Magento backend access: roles define ACL permissions, and users are assigned to those roles. Roles must be created before users that reference them.

## Admin Roles Component

- **Alias**: `adminroles`
- **PHP class**: `CtiDigital\Configurator\Component\AdminRoles`
- **Samples**: `Samples/Components/AdminRoles/adminroles.yaml`, `Samples/Components/AdminRoles/apiroles.yaml`

### YAML Schema

```yaml
adminroles:
  - name: "<role name>"
    resources:
      - '<Magento_Module::acl_resource>'
      - '<Magento_Module::acl_resource>'
  - name: "<role name>"
    resources:
      - '<Magento_Module::acl_resource>'
```

### Fields

| Field       | Required | Description |
|-------------|----------|-------------|
| `name`      | Yes      | Display name of the admin role. Used to identify existing roles for updates. |
| `resources` | Yes      | Array of Magento ACL resource identifiers. If null or empty, an error is logged. |

### ACL Resource IDs

Resources use Magento's ACL identifier format: `<Module>::<resource>`. Common examples:

- `Magento_Backend::admin` -- top-level admin access
- `Magento_Backend::dashboard` -- dashboard access
- `Magento_Sales::sales` -- sales section
- `Magento_Sales::create` -- create orders
- `Magento_Sales::actions_view` -- view order actions
- `Magento_Catalog::catalog` -- catalog section

To grant full admin access, leave `resources` empty or null (the component currently logs an error for null resources, so always provide at least one resource).

### Example

```yaml
adminroles:
  - name: "Sales Users"
    resources:
      - 'Magento_Backend::dashboard'
      - 'Magento_Backend::admin'
      - 'Magento_Sales::sales'
      - 'Magento_Sales::create'
      - 'Magento_Sales::actions_view'
      - 'Magento_Sales::actions_edit'
      - 'Magento_Sales::cancel'
      - 'Magento_Sales::hold'
      - 'Magento_Sales::unhold'
  - name: "Tax Manager"
    resources:
      - 'Magento_Backend::dashboard'
      - 'Magento_Sales::sales'
      - 'Magento_Sales::sales_operation'
      - 'Magento_Sales::sales_order'
      - 'Magento_Sales::actions'
      - 'Magento_Sales::create'
      - 'Magento_Sales::actions_view'
      - 'Magento_Sales::email'
      - 'Magento_Sales::reorder'
```

### API Roles

API roles use the same `adminroles` component alias and identical YAML structure. They are simply defined in a separate YAML file (e.g. `apiroles.yaml`) and referenced as a separate source in the master configuration. The structure is exactly the same.

---

## Admin Users Component

- **Alias**: `adminusers`
- **PHP class**: `CtiDigital\Configurator\Component\AdminUsers`
- **Samples**: `Samples/Components/AdminUsers/adminusers.yaml`, `Samples/Components/AdminUsers/apiusers.yaml`

### YAML Schema

```yaml
adminusers:
  - rolename: <role name>
    users:
      - username: <username>
        firstname: <first name>
        secondname: <last name>
        email: <email>
        password: <password>
        interface_locale: <locale_code>   # optional
  - rolename: <role name>
    users:
      - username: <username>
        firstname: <first name>
        secondname: <last name>
        email: <email>
        password: <password>
```

### Fields

| Field              | Required | Description |
|--------------------|----------|-------------|
| `rolename`         | Yes      | Name of an existing admin role. Must match a role created by the `adminroles` component. |
| `username`         | Yes      | Login username for the admin user. |
| `firstname`        | Yes      | User's first name. |
| `secondname`       | Yes      | User's last name. |
| `email`            | Yes      | Email address. Used as the unique identifier -- if a user with this email already exists, creation is skipped. |
| `password`         | Yes      | Password for the user account. |
| `interface_locale` | No       | Admin panel locale (e.g. `en_US`, `fr_FR`). Only set if specified. |

### Role-User Relationship

Users are grouped under their role name. The component resolves the `rolename` to a role ID at runtime. If the role does not exist, an error is logged and processing stops for that role group. This means **roles must be processed before users** in your master YAML configuration.

### Example

```yaml
adminusers:
  - rolename: Sales Users
    users:
      - username: storeadmin
        firstname: store
        secondname: admin
        email: storeadmin@email.com
        password: fakepassword00
  - rolename: Tax Manager
    users:
      - username: taxmanager
        firstname: tax
        secondname: manager
        email: taxmanager@email.com
        password: Fakepassword01
```

### API Users

API users use the same `adminusers` component alias and identical YAML structure. They are defined in a separate YAML file (e.g. `apiusers.yaml`) and referenced as a separate source in the master configuration. The structure is exactly the same.

---

## Ordering in Master YAML

Because users depend on roles, ensure the `adminroles` component sources appear before `adminusers` in your master YAML:

```yaml
adminroles:
  - ../configurator/AdminRoles/adminroles.yaml
  - ../configurator/AdminRoles/apiroles.yaml

adminusers:
  - ../configurator/AdminUsers/adminusers.yaml
  - ../configurator/AdminUsers/apiusers.yaml
```

## Processing Behavior

- **Roles**: If a role with the same name already exists, its ACL resources are updated. New roles are created with `RoleGroup` type and `USER_TYPE_ADMIN` user type.
- **Users**: If a user with the same email already exists, creation is skipped entirely (no update). New users are validated by Magento's user model before saving.
