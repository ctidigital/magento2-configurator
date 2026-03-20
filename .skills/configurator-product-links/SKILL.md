---
name: configurator-product-links
description: Use when the user wants to create product relationships (related, upsell, cross-sell) via the configurator. Triggers on mentions of related products, upsells, cross-sells, product links.
---

# Configurator: Product Links Component

## Purpose

Creates and maintains product link relationships: related products, up-sells, and cross-sells. Links are set on a per-product basis, mapping a source SKU to an array of linked SKUs.

- **Component alias:** `product_links`
- **File format:** YAML
- **Dependencies:** All referenced products (both source and linked SKUs) must exist before this component runs.

## YAML Schema

The file has up to three root keys corresponding to the three link types. Each key maps a source SKU to an array of linked SKUs.

```yaml
relation:                    # Optional. Related products.
  <source_sku>:
    - <linked_sku_1>
    - <linked_sku_2>

up_sell:                     # Optional. Up-sell products.
  <source_sku>:
    - <linked_sku_1>
    - <linked_sku_2>

cross_sell:                  # Optional. Cross-sell products.
  <source_sku>:
    - <linked_sku_1>
    - <linked_sku_2>
```

### Link Types

| YAML Key | Magento Link Type | Description |
|----------|-------------------|-------------|
| `relation` | `related` | Related products shown on the product detail page. |
| `up_sell` | `upsell` | Up-sell products shown on the product detail page (higher value alternatives). |
| `cross_sell` | `crosssell` | Cross-sell products shown in the shopping cart (complementary items). |

### Field Details

| Field | Required | Description |
|-------|----------|-------------|
| `<source_sku>` | Yes | The SKU of the product to attach links to. Must exist in the catalog. |
| Linked SKU array | Yes | Array of SKUs to link. Each must exist in the catalog. Position is assigned automatically (increments of 10). |

## Complete Example

A single file can contain all three link types:

```yaml
relation:
  simple-product:
    - configurable_product_1
    - configurable_product_2

up_sell:
  configurable_product_1:
    - simple-product
    - configurable_product_2

cross_sell:
  configurable_product_2:
    - simple-product
    - configurable_product_1
```

Or use separate files per link type and reference them individually in master.yaml.

## Instructions for Claude

### Creating a new product links YAML file

1. Create a `.yaml` file (e.g., `configurator/product-links/related.yaml`).
2. Use one or more of the three root keys: `relation`, `up_sell`, `cross_sell`.
3. Under each root key, add source SKUs as keys, each with an array of linked SKUs.
4. All referenced SKUs must correspond to existing products.

### Amending an existing file

1. Read the existing file first.
2. To add links to a new product, add a new SKU key under the appropriate link type.
3. To add more linked products, append SKUs to the existing array.
4. Note: the component replaces all links of the given type on the source product. If you remove a SKU from the array, that link will be removed on the next run.

### Registering in master.yaml

```yaml
product_links:
  enabled: 1
  method: code
  sources:
    - ../configurator/product-links/related.yaml
    - ../configurator/product-links/up-sells.yaml
    - ../configurator/product-links/cross-sells.yaml
```

Place `product_links` after the `products` component so that all referenced SKUs exist.

## Processing Behavior

- The component iterates over each link type, then each source SKU, then each linked SKU.
- Product link objects are created with `ProductLinkInterfaceFactory` and saved via `setProductLinks` on the source product.
- Position is assigned automatically based on array order (0, 10, 20, ...).
- If a source SKU or linked SKU does not exist, an error is logged and that entry is skipped.
- Running the component replaces all links of the processed type on the source product with the set defined in YAML.

## Common Mistakes

1. **Using wrong root key names.** The keys must be exactly `relation`, `up_sell`, or `cross_sell`. Do not use `related`, `upsell`, or `crosssell` (those are the Magento internal names, not the YAML keys).
2. **Referencing non-existent SKUs.** Both the source and linked SKUs must exist in the catalog. The component will log an error and skip if a SKU is not found.
3. **Forgetting that links are replaced, not merged.** The component sets the full list of links from the YAML. Any existing links of that type not present in the YAML will be removed.
4. **Running before products component.** Ensure the `products` component has run first so all SKUs exist.
