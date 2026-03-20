---
name: configurator-categories
description: Use when the user wants to create or modify Magento category trees via the configurator. Triggers on mentions of categories, subcategories, category tree, category images, landing pages, category CMS blocks.
---

# Configurator Categories Component

## Purpose

Creates and updates Magento categories recursively in the category tree. Categories are defined in a YAML file and processed top-down, creating parent categories before children.

## Component Alias

`categories`

Used in the master YAML configurator file as:

```yaml
components:
  categories:
    sources:
      - path/to/categories.yaml
```

## File Format

YAML. The top-level key is `categories`, containing an array of store group entries.

## Schema

```yaml
categories:
  -
    store_group: "Main Website Store"   # Required. Must match an existing store group name.
    categories:                          # Array of top-level categories under this store group.
      -
        name: "Category Name"           # Required. Used to identify and match existing categories.
        is_active: true                  # Optional. Defaults to true if omitted.
        position: 1                      # Optional. Sort order.
        include_in_menu: true            # Optional.
        description: "Description text"  # Optional.
        url_key: "category-name"         # Optional. Custom attribute, set via setCustomAttribute.
        image: "https://example.com/image.jpg"  # Optional. See image handling below.
        landing_page: "block-identifier" # Optional. CMS block identifier or numeric block ID.
        display_mode: "PRODUCTS_AND_PAGE" # Optional. Required when using landing_page.
        cms_block: "Block Title"         # Optional. Legacy alternative to landing_page.
        categories:                      # Optional. Nested subcategories (recursive).
          - name: "Subcategory"
            # ... same attributes available
```

## Attribute Handling

### Main Attributes (set via `setData`)

These are set directly on the category model:

- `name` - Category name (also used to look up existing categories by parent)
- `is_active` - Whether the category is active (defaults to `true` if not specified)
- `position` - Sort position
- `include_in_menu` - Show in navigation menu
- `description` - Category description

### Image Attributes

Any attribute named `image`, or any attribute whose EAV frontend_input is `image` or whose backend model is `Magento\Catalog\Model\Category\Attribute\Backend\Image`, is treated as an image.

The value can be:

- A full URL (e.g. `https://example.com/photo.jpg`) - downloaded and copied
- A relative path from Magento root (e.g. `media/import/photo.jpg`) - resolved against BP

The file is copied to `pub/media/catalog/category/` and the database stores `/media/catalog/category/filename.jpg`.

### Landing Page (CMS Block - Preferred)

```yaml
landing_page: "block-identifier"    # CMS block identifier string
# or
landing_page: 42                    # Numeric CMS block ID
```

The value is resolved via `CmsBlockResolver`. When using an identifier, the resolver looks up the block ID. You must also set `display_mode` explicitly.

### CMS Block (Legacy)

```yaml
cms_block: "Block Title"
```

Looks up a CMS block by its **title** (not identifier). Automatically sets `display_mode` to `PRODUCTS_AND_PAGE`. Prefer `landing_page` with the block identifier for new configurations.

### Display Mode

Valid values:

- `PRODUCTS` - Show products only
- `PAGE` - Show CMS block only
- `PRODUCTS_AND_PAGE` - Show both products and CMS block

Must be set when using `landing_page`. Automatically set to `PRODUCTS_AND_PAGE` when using `cms_block`.

### Custom Attributes

Any attribute not in the main attributes list and not an image/landing_page/cms_block is set via `setCustomAttribute`. This includes attributes like `url_key`, `meta_title`, `meta_description`, custom EAV attributes, etc.

## Complete Example

```yaml
categories:
  -
    store_group: Main Website Store
    categories:
      -
        name: "Clothing"
        description: "All clothing products"
        url_key: "clothing"
        is_active: true
        include_in_menu: true
        image: "https://example.com/clothing-banner.jpg"
        landing_page: "clothing-landing-block"
        display_mode: "PRODUCTS_AND_PAGE"
        categories:
          -
            name: "Men"
            description: "Men's clothing"
            url_key: "mens-clothing"
            position: 1
            categories:
              -
                name: "T-Shirts"
                description: "Men's t-shirts"
                url_key: "mens-t-shirts"
              -
                name: "Jeans"
                description: "Men's jeans"
                url_key: "mens-jeans"
          -
            name: "Women"
            description: "Women's clothing"
            url_key: "womens-clothing"
            position: 2
      -
        name: "Accessories"
        description: "Accessories and extras"
        url_key: "accessories"
        is_active: true
      -
        name: "Sale"
        description: "Sale items"
        url_key: "sale"
        landing_page: "sale-promo-block"
        display_mode: "PAGE"
```

## Dependencies

- **websites**: The `store_group` value must match an existing Magento store group name. The `websites` component should run first.
- **blocks**: If using `landing_page` or `cms_block`, the referenced CMS block must already exist. The `blocks` component should run before `categories`.

## Instructions for Claude

When creating a category tree:

1. Ask the user for the store group name (default is `Main Website Store`).
2. Structure categories as nested YAML. Each level of nesting creates a child category.
3. Always include `name` for every category -- it is the identifier used to match existing categories by parent.
4. Set `url_key` for SEO-friendly URLs. Use lowercase with hyphens.
5. To attach a CMS block as a landing page, use `landing_page` with the block's identifier and set `display_mode` to the appropriate value.
6. For images, provide either a full URL or a path relative to the Magento root directory.
7. Categories default to `is_active: true` if not specified.

When adding subcategories to an existing tree, nest them under the parent category in the YAML. The component matches by `name` + `parent_id`, so existing categories are updated rather than duplicated.

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Wrong `store_group` name | Must exactly match a Magento store group name (e.g. `Main Website Store`). Check admin under Stores > All Stores. |
| Using `landing_page` without `display_mode` | Always set `display_mode` to `PRODUCTS_AND_PAGE` or `PAGE` when assigning a landing page, otherwise the block will not render. |
| Confusing `landing_page` and `cms_block` | `landing_page` takes a block **identifier** (or ID); `cms_block` takes a block **title**. Prefer `landing_page`. |
| Image path not found | URLs must be fully qualified (`https://...`). Relative paths are resolved from the Magento root (`BP`). The file must be accessible at import time. |
| Missing `name` attribute | `name` is required for every category entry. It is used to look up existing categories. |
| Expecting `category` key for nesting | The nesting key is `categories` (plural), not `category`. |
