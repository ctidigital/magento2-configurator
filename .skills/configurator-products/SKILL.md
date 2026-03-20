---
name: configurator-products
description: Use when the user wants to import products via the configurator. Triggers on mentions of products, SKUs, product import, product CSV, simple products, configurable products, product images.
---

# Configurator Products Component

## Purpose

Imports products into Magento via CSV using the FastSimpleImport library (which wraps Magento's native import framework). Supports simple and configurable product types. Products are validated before import, and invalid rows are automatically removed.

## Component Alias

`products`

Used in the master YAML configurator file as:

```yaml
components:
  products:
    sources:
      - path/to/simple.csv
      - path/to/configurable.csv
```

## File Format

CSV with comma (`,`) as the field delimiter. The first row must be the header row containing attribute codes as column names.

**Multi-value separator**: Semicolon (`;`) is used to separate multiple values within a single field (e.g. `product_websites`, `store_view_code`). Fields like `product_websites` and `store_view_code` that use commas in the CSV are automatically converted to semicolons internally.

## CSV Schema

### Required Columns

| Column | Description |
|--------|-------------|
| `sku` | Unique product identifier |
| `attribute_set_code` | Attribute set name (e.g. `Default`) |
| `product_type` | `simple` or `configurable` |
| `name` | Product name |
| `price` | Product price |
| `product_websites` | Website code(s), comma-separated in CSV (e.g. `base`) |
| `visibility` | `catalog, search`, `catalog`, `search`, or `not visible individually` |

### Common Optional Columns

| Column | Description |
|--------|-------------|
| `url_key` | SEO-friendly URL slug |
| `short_description` | Short product description |
| `description` | Full product description |
| `qty` | Stock quantity |
| `is_in_stock` | `1` or `0`. If `is_in_stock=1` and `qty` is missing, qty defaults to `1` |
| `weight` | Product weight |
| `tax_class_name` | Tax class name (e.g. `Taxable Goods`) |
| `meta_title` | SEO meta title |
| `meta_keywords` | SEO meta keywords |
| `meta_description` | SEO meta description |

### Image Columns

| Column | Description |
|--------|-------------|
| `image` | Main product image path or URL |
| `small_image` | Small image path or URL |
| `thumbnail` | Thumbnail image path or URL |
| `additional_images` | Additional images, separated by `;` |

Image values can be:
- Full URLs (e.g. `https://example.com/image.jpg`) - downloaded automatically
- Paths relative to `pub/media/` (e.g. `import/product1.jpg`)

### Configurable Product Columns

These columns are only used for `product_type: configurable`:

| Column | Description |
|--------|-------------|
| `associated_products` | Comma-separated list of child SKUs (e.g. `sku1,sku2,sku3`) |
| `configurable_attributes` | Comma-separated list of configurable attribute codes (e.g. `color,size`) |

The component builds the `configurable_variations` string automatically from these two columns by loading each associated product and reading its attribute values.

### Custom Attribute Columns

Any column header that does not match a built-in column is treated as a product attribute. The attribute code in the header must match the Magento attribute code. If the attribute option does not exist, the component creates it automatically.

## Description Handling

Newlines in `description` and `short_description` fields are automatically wrapped in `<p>` paragraph tags unless the value already contains `<p>` tags. This ensures proper formatting on the frontend.

## Simple Product CSV Example

```csv
attribute_set_code,product_websites,product_type,sku,name,short_description,description,price,url_key,visibility,meta_title,meta_keywords,meta_description,color,image,small_image,thumbnail
Default,base,simple,tshirt-blue-m,Blue T-Shirt Medium,A comfortable blue t-shirt.,Our premium cotton blue t-shirt in medium size.,19.99,blue-tshirt-medium,"catalog, search",Blue T-Shirt,clothing tshirt blue,A premium blue t-shirt,Blue,https://example.com/blue-tshirt.jpg,https://example.com/blue-tshirt.jpg,https://example.com/blue-tshirt.jpg
Default,base,simple,tshirt-red-m,Red T-Shirt Medium,A comfortable red t-shirt.,Our premium cotton red t-shirt in medium size.,19.99,,not visible individually,Red T-Shirt,clothing tshirt red,A premium red t-shirt,Red,https://example.com/red-tshirt.jpg,https://example.com/red-tshirt.jpg,https://example.com/red-tshirt.jpg
```

## Configurable Product CSV Example

```csv
attribute_set_code,product_websites,product_type,sku,name,short_description,description,price,url_key,visibility,meta_title,meta_keywords,meta_description,associated_products,configurable_attributes,image,small_image,thumbnail
Default,base,configurable,tshirt-config,T-Shirt,A comfortable t-shirt.,Our premium cotton t-shirt available in multiple colors.,19.99,t-shirt,"catalog, search",T-Shirt,clothing tshirt,A premium t-shirt,"tshirt-blue-m,tshirt-red-m",color,https://example.com/tshirt.jpg,https://example.com/tshirt.jpg,https://example.com/tshirt.jpg
```

**Important**: Simple products that are children of a configurable should have visibility set to `not visible individually`. The configurable parent should be `catalog, search`.

**Important**: The associated simple products must already exist in Magento (imported first in a separate CSV or earlier in the same run) before the configurable product CSV is processed. The component loads each associated SKU to read its attribute values.

## Dependencies

- **attributes**: Custom product attributes must exist before import. The component auto-creates attribute option values but not the attributes themselves.
- **attribute_sets**: The `attribute_set_code` value must match an existing attribute set.
- **media**: Image files must be accessible at the URLs or paths specified at import time.

## Instructions for Claude

When creating product CSVs:

1. Start with the header row. Include all required columns plus any custom attributes.
2. For simple products, set `product_type` to `simple`.
3. For configurable products, create two CSV files:
   - First CSV: all simple child products with `visibility` set to `not visible individually`
   - Second CSV: configurable parent product with `associated_products` (comma-separated child SKUs) and `configurable_attributes` (comma-separated attribute codes like `color,size`)
4. Ensure `attribute_set_code` matches exactly what is in Magento (typically `Default`).
5. Use full URLs for images when importing from external sources.
6. For multi-value fields within a cell, use `;` as the separator.
7. Any column not recognized as a standard Magento column is treated as a custom attribute code. Attribute option values are created automatically if they do not exist.
8. Every data row must have the same number of columns as the header row, or it will be skipped.

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Using `;` as CSV field delimiter | The CSV field delimiter is comma (`,`). Semicolon (`;`) is only the multi-value separator within a field. |
| Missing required columns | Every row must have `sku`, `attribute_set_code`, `product_type`, `name`, `price`, `product_websites`, `visibility`. |
| `attribute_set_code` mismatch | The value must exactly match an attribute set name in Magento (case-sensitive). Check admin under Stores > Attributes > Attribute Set. |
| Column count mismatch | Every data row must have exactly the same number of columns as the header. Rows with mismatched column counts are silently skipped. |
| Importing configurable before simple products | Associated simple products must exist in Magento before the configurable CSV is processed. Import simples first. |
| Wrong visibility for child products | Simple products that are children of a configurable should be `not visible individually`. |
| Images not accessible | Image URLs must be reachable at import time. Local paths are relative to `pub/media/`. |
| Using `\n` for newlines in descriptions | Newlines in the CSV cell are automatically wrapped in `<p>` tags. If the value already has `<p>` tags, automatic wrapping is skipped. |
