---
name: configurator-shipping-table-rates
description: Use when the user wants to configure shipping table rates via the configurator. Triggers on mentions of shipping rates, table rates, shipping costs, delivery charges.
---

# Shipping Table Rates Component

Alias: `shippingtablerates`

Configures shipping table rates per website. Each website code is a root key containing an array of rate entries.

## YAML Schema

```yaml
<website_code>:
  -
    dest_country_id: <string>
    dest_region_code: <string>
    dest_zip: <string>
    condition_name: <string>
    condition_value: <number>
    price: <number>
    cost: <number>
```

- Root keys are website codes (e.g. `base`, `usa`)
- Each website contains an array of rate objects
- `dest_country_id`: two-letter ISO country code (e.g. `GB`, `US`, `DE`)
- `dest_region_code`: region/state code, or `"*"` for all regions
- `dest_zip`: postcode/zip, or `"*"` for all
- `condition_name`: the condition type (e.g. `package_value`, `package_weight`, `package_qty`)
- `condition_value`: threshold value for the condition
- `price`: shipping price charged to customer
- `cost`: internal shipping cost

## Sample Configuration

```yaml
base:
  -
    dest_country_id: DE
    dest_region_code: BER
    dest_zip: 10405
    condition_name: package_value
    condition_value: 1.99
    price: 5.99
    cost: 1.99
  -
    dest_country_id: GB
    dest_region_code: "*"
    dest_zip: "*"
    condition_name: package_value
    condition_value: 0
    price: 3.99
    cost: 1
usa:
  -
    dest_country_id: US
    dest_region_code: "*"
    dest_zip: "*"
    condition_name: package_value
    condition_value: 1.99
    price: 4.99
    cost: 1
```

## Notes

- Website codes must match existing Magento website codes
- Use `"*"` as a wildcard for region and zip to apply rates broadly
- Rates are matched in order of specificity by Magento
