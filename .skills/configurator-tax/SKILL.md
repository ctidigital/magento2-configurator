---
name: configurator-tax
description: Use when the user wants to create or modify tax rates or tax rules via the configurator. Triggers on mentions of tax rates, tax rules, VAT, tax percentages, tax classes.
---

# Tax Components

This skill covers two related components: **Tax Rates** and **Tax Rules**. Tax rules reference tax rates by code, so rates should be imported first.

---

## Tax Rates

- **Alias**: `taxrates`
- **Format**: CSV
- **Source file**: `Component/TaxRates.php`
- **Samples**: `Samples/Components/TaxRates/taxrates.csv`, `Samples/Components/TaxRates/eu_taxrates.csv`
- **Dependencies**: None

### CSV Headers

| Column            | Required | Description                                                    |
|-------------------|----------|----------------------------------------------------------------|
| `code`            | yes      | Unique identifier for the tax rate (e.g. `US-CA-*-Rate 1`)    |
| `tax_country_id`  | yes      | Two-letter ISO country code (e.g. `US`, `GB`, `DE`)           |
| `tax_region_id`   | yes      | Region/state code (e.g. `CA`, `NY`) or `*` for all regions    |
| `tax_postcode`    | yes      | Postcode filter or `*` for all postcodes                       |
| `rate`            | yes      | Tax percentage (e.g. `20` for 20%, `8.25` for 8.25%)          |
| `zip_is_range`    | no       | Set to `1` to enable ZIP range filtering                       |
| `zip_from`        | no       | Start of ZIP range (integer, used when `zip_is_range` = 1)    |
| `zip_to`          | no       | End of ZIP range (integer, used when `zip_is_range` = 1)      |

### Behavior

- Rates are matched by `code`. Existing rates are skipped (idempotent).
- Region codes (e.g. `CA`) are resolved to Magento region IDs. Use `*` or `0` for all regions.
- Postcodes accept `*` as wildcard for all postcodes.

### Example

```csv
code,tax_country_id,tax_region_id,tax_postcode,rate,zip_is_range,zip_from,zip_to
US-CA-*-Rate 1,US,CA,*,8.2500,,,
US-NY-*-Rate 1,US,NY,*,8.3750,,,
EU-GB-Standard,GB,*,*,20,,,
EU-DE-Standard,DE,*,*,19,,,
EU-DE-Reduced,DE,*,*,7,,,
```

For ZIP range filtering:

```csv
code,tax_country_id,tax_region_id,tax_postcode,rate,zip_is_range,zip_from,zip_to
US-CA-ZIP-Range,US,CA,*,9.0000,1,90000,90999
```

---

## Tax Rules

- **Alias**: `taxrules`
- **Format**: CSV
- **Source file**: `Component/TaxRules.php`
- **Dependencies**: Tax rates must exist (referenced by code); tax classes are auto-created if missing

### CSV Headers

| Column                   | Required | Description                                                       |
|--------------------------|----------|-------------------------------------------------------------------|
| `code`                   | yes      | Unique rule name/code                                             |
| `tax_rate_ids`           | yes      | Comma-separated tax rate codes (e.g. `US-CA-*-Rate 1,US-NY-*-Rate 1`) |
| `customer_tax_class_ids` | yes      | Comma-separated customer tax class names (e.g. `Retail Customer`) |
| `product_tax_class_ids`  | yes      | Comma-separated product tax class names (e.g. `Taxable Goods`)   |
| `priority`               | no       | Rule priority (integer, default 0)                                |
| `calculate_subtotal`     | no       | Whether to calculate on subtotal (0 or 1, default 0)             |
| `position`               | no       | Sort order/position (integer, default 0)                          |

### Behavior

- Rules are matched by `code`. Existing rules are skipped (idempotent).
- Tax rate codes in `tax_rate_ids` are resolved to internal IDs via the tax rate repository. Rates must already exist.
- Customer and product tax class names are resolved by name. If a tax class does not exist, it is automatically created with the appropriate type (CUSTOMER or PRODUCT).
- Multiple tax rates or tax classes can be assigned to a single rule using comma-separated values.

### Example

```csv
code,tax_rate_ids,customer_tax_class_ids,product_tax_class_ids,priority,calculate_subtotal,position
US Standard Tax,US-CA-*-Rate 1,Retail Customer,Taxable Goods,0,0,1
US Multi-State Tax,"US-CA-*-Rate 1,US-NY-*-Rate 1",Retail Customer,Taxable Goods,1,1,2
EU VAT Rule,EU-GB-Standard,Retail Customer,Taxable Goods,0,0,1
```

---

## Relationship Between Tax Rates and Tax Rules

```
Tax Rates (taxrates)          Tax Rules (taxrules)
---------------------         ----------------------
code: US-CA-*-Rate 1    <--   tax_rate_ids: US-CA-*-Rate 1
code: US-NY-*-Rate 1    <--   tax_rate_ids: US-NY-*-Rate 1

Tax Classes (auto-created)
--------------------------
Retail Customer (CUSTOMER)  <--  customer_tax_class_ids
Taxable Goods (PRODUCT)     <--  product_tax_class_ids
```

Import order: tax rates first, then tax rules.

## Configurator YAML Reference

```yaml
components:
  taxrates:
    sources:
      - taxrates.csv
      - eu_taxrates.csv
  taxrules:
    sources:
      - taxrules.csv
```
