---
name: configurator-customers
description: Use when the user wants to import customers via the configurator. Triggers on mentions of customer import, customer CSV, customer data.
---

# Customers Component

- **Alias**: `customers`
- **Format**: CSV
- **Source file**: `Component/Customers.php`
- **Sample**: `Samples/Components/Customers/customers.csv`
- **Import mechanism**: Magento native import (`customer_composite` entity)
- **Dependencies**: None (customer groups should exist beforehand if referencing by ID)

## CSV Headers

The first row must contain column headers. Three columns are required; additional columns are optional and map to standard Magento customer/address import fields.

### Required Columns

| Column     | Description                              |
|------------|------------------------------------------|
| `email`    | Customer email address (primary key)     |
| `_website` | Website code (e.g. `base`)               |
| `_store`   | Store code (e.g. `admin`, `default`)     |

### Common Optional Columns

| Column                        | Description                                    |
|-------------------------------|------------------------------------------------|
| `firstname`                   | Customer first name                            |
| `lastname`                    | Customer last name                             |
| `middlename`                  | Customer middle name                           |
| `prefix`                      | Name prefix                                    |
| `gender`                      | Gender (1 = Male, 2 = Female)                  |
| `group_id`                    | Customer group ID (validated; defaults to store default if invalid) |
| `_address_city`               | Address city                                   |
| `_address_company`            | Address company                                |
| `_address_country_id`         | Two-letter country code (e.g. `GB`, `US`)      |
| `_address_fax`                | Fax number                                     |
| `_address_firstname`          | Address first name                             |
| `_address_lastname`           | Address last name                              |
| `_address_middlename`         | Address middle name                            |
| `_address_postcode`           | Postal/ZIP code                                |
| `_address_prefix`             | Address name prefix                            |
| `_address_region`             | Region/state name                              |
| `_address_street`             | Street address                                 |
| `_address_suffix`             | Address name suffix                            |
| `_address_telephone`          | Phone number                                   |
| `_address_vat_id`             | VAT ID                                         |
| `_address_default_billing_`   | Set as default billing (1 = yes, 0 = no)       |
| `_address_default_shipping_`  | Set as default shipping (1 = yes, 0 = no)      |

## Multiple Addresses

To assign multiple addresses to a single customer, add extra rows immediately after the customer row with the `email` column left empty. The component detects blank email fields as additional address rows for the preceding customer.

## Behavior

- Uses `Import::BEHAVIOR_APPEND` -- existing customers matched by email are updated, new customers are created.
- Invalid `group_id` values are replaced with the store's default customer group.
- After import, the `customer_grid` indexer is reindexed automatically.
- Import errors are logged individually.

## Example

```csv
email,_website,_store,firstname,lastname,group_id,_address_city,_address_country_id,_address_postcode,_address_street,_address_telephone,_address_default_billing_,_address_default_shipping_
john@example.com,base,default,John,Doe,1,London,GB,SW1A 1AA,10 Downing Street,02071234567,1,1
,,,,,,,Manchester,GB,M1 1AA,50 Market Street,01611234567,0,0
jane@example.com,base,default,Jane,Smith,1,New York,US,10001,123 Broadway,2125551234,1,1
```

## Configurator YAML Reference

```yaml
components:
  customers:
    sources:
      - customers.csv
```
