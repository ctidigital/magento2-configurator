---
name: configurator-tiered-prices
description: Use when the user wants to import tiered/quantity-based pricing via the configurator. Triggers on mentions of tiered prices, quantity discounts, tier pricing, volume pricing.
---

# Tiered Prices Component

Alias: `tiered_prices`

Imports quantity-based tier pricing from a CSV file using Magento's native advanced-pricing import (`advanced_pricing` entity).

## CSV Format

Headers: `sku`, `tier_price_website`, `tier_price_customer_group`, `tier_price_qty`, `tier_price`, `tier_price_value_type`

- `sku`: product SKU
- `tier_price_website`: website scope (e.g. `All Websites [GBP]`)
- `tier_price_customer_group`: customer group name (e.g. `ALL GROUPS`) or a specific group
- `tier_price_qty`: minimum quantity to trigger the tier price
- `tier_price`: the tier price value
- `tier_price_value_type`: `Fixed` for an absolute price, `Discount` for a percentage discount

## Sample CSV

```csv
sku,tier_price_website,tier_price_customer_group,tier_price_qty,tier_price,tier_price_value_type
SKU1,All Websites [GBP],ALL GROUPS,10,0.85,Fixed
SKU1,All Websites [GBP],ALL GROUPS,50,0.79,Fixed
SKU2,All Websites [GBP],ALL GROUPS,24,2.99,Fixed
```

## Notes

- File format is CSV (not YAML)
- Uses Magento's native import framework under the hood for bulk processing
- Multiple tier rows per SKU are supported for different quantities
- Website value must match the Magento website name including currency code in brackets
