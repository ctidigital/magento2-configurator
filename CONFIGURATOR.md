# CONFIGURATOR.md — magento2-configurator AI Skill Orchestrator

This document helps AI agents understand the magento2-configurator extension and route requests to the correct component skill in `.skills/`.

For guidance on **developing the PHP codebase**, see `AGENTS.md` instead.

---

## What is the Configurator?

The magento2-configurator extension allows Magento 2 stores to be configured declaratively via YAML and CSV files. A single CLI command applies the configuration:

```bash
bin/magento configurator:run --env="local"
```

Configuration is organised into **components** (websites, attributes, categories, products, CMS blocks, etc.), each processing its own data files. A **master.yaml** file orchestrates which components run and where their data files are located.

---

## Component Execution Order

Components execute in this fixed order (defined in `etc/di.xml`). **Your master.yaml should follow this order:**

| Order | Alias | Description | Skill |
|-------|-------|-------------|-------|
| 1 | `websites` | Websites, store groups, store views | `configurator-websites` |
| 2 | `config` | System configuration (path/value) | `configurator-config` |
| 3 | `sequence` | Sales sequence table overrides | `configurator-sequence` |
| 4 | `attributes` | Product EAV attributes | `configurator-attributes` |
| 5 | `attribute_sets` | Attribute sets and groups | `configurator-attribute-sets` |
| 6 | `adminroles` | Admin roles with ACL resources | `configurator-admin` |
| 7 | `adminusers` | Admin users | `configurator-admin` |
| 8 | `customergroups` | Customer groups | `configurator-customer-groups` |
| 9 | `categories` | Category tree | `configurator-categories` |
| 10 | `taxrates` | Tax rates | `configurator-tax` |
| 11 | `taxrules` | Tax rules | `configurator-tax` |
| 12 | `products` | Product import (CSV) | `configurator-products` |
| 13 | `blocks` | CMS static blocks | `configurator-cms` |
| 14 | `pages` | CMS pages | `configurator-cms` |
| 15 | `apiintegrations` | OAuth API integrations | `configurator-api-integrations` |
| 16 | `widgets` | CMS widgets | `configurator-widgets` |
| 17 | `media` | Media file download/copy | `configurator-media` |
| 18 | `rewrites` | URL rewrites | `configurator-rewrites` |
| 19 | `review_rating` | Product review ratings | `configurator-review-rating` |
| 20 | `product_links` | Related/upsell/cross-sell | `configurator-product-links` |
| 21 | `customers` | Customer import (CSV) | `configurator-customers` |
| 22 | `catalog_price_rules` | Catalog price rules | `configurator-catalog-price-rules` |
| 23 | `sql` | Raw SQL execution | `configurator-sql` |
| 24 | `shippingtablerates` | Shipping table rates | `configurator-shipping-table-rates` |
| 25 | `customer_attributes` | Customer EAV attributes | `configurator-customer-attributes` |
| 26 | `tiered_prices` | Tiered prices (CSV) | `configurator-tiered-prices` |
| 27 | `order_statuses` | Custom order statuses | `configurator-order-statuses` |

### Why Order Matters

- **Websites** must come first — store groups and store views are referenced by code in nearly every other component
- **Attributes** before **attribute_sets** — sets reference attributes by code
- **Attributes** before **products** — product CSV columns must match defined attribute codes
- **Blocks** before **categories** — categories can reference CMS blocks via `landing_page`
- **Media** before **categories/products** — image files must exist before they're assigned
- **Tax rates** before **tax rules** — rules reference rates by code
- **Admin roles** before **admin users** — users are assigned to roles
- **SQL** should be last or near-last — it's a raw escape hatch

---

## master.yaml Structure

Located at `app/etc/master.yaml` in the Magento root. Each component entry:

```yaml
component_alias:
  enabled: 1                     # 0 = skip, 1 = run
  sources:                       # Files processed for all environments
    - ../configurator/path/to/file.yaml
    - ../configurator/path/to/another.csv
  env:                           # Optional environment-specific config
    local:
      mode: maintain             # Optional: "maintain" mode
      log: debug                 # Log level: debug, info, notice, warning, error
      sources:                   # Additional sources for this env only
        - ../configurator/path/to/local-override.yaml
    production:
      log: error
```

### Key Properties

- **`enabled`**: `0` disables the component entirely; `1` enables it
- **`sources`**: Array of file paths relative to `app/etc/` (the Magento root's config directory). Always processed regardless of environment.
- **`env`**: Environment-specific overrides. Only the block matching the `--env` CLI argument is applied.
  - **`sources`**: Additional files processed *after* global sources (additive, not replacing)
  - **`mode: maintain`**: When set, the component updates existing entities but does not create new ones
  - **`log`**: Override log verbosity for this component in this environment

### Environment System

```bash
# Run with local environment overrides
bin/magento configurator:run --env="local"

# Run with no environment (global sources only)
bin/magento configurator:run
```

Global `sources` always execute first, then environment-specific `sources` are appended. This allows a base configuration shared across all environments with environment-specific additions (e.g., local test data, production-only settings).

---

## File Path Conventions

Typical project structure:

```
app/
  etc/
    master.yaml                          # Main orchestration file
configurator/                            # All data files (sibling to app/)
  Websites/
    websites.yaml
  Configuration/
    global.yaml
    base-website-config.yaml
  Attributes/
    attributes.yaml
    attribute_sets.yaml
  Categories/
    categories.yaml
  Products/
    simple.csv
    configurable.csv
  Blocks/
    blocks.yaml
    allstores.html                       # HTML source files for blocks
  Pages/
    pages.yaml
    homepage.html
  ...
```

Sources in master.yaml use paths relative to `app/etc/`:
```yaml
sources:
  - ../configurator/Attributes/attributes.yaml    # resolves to configurator/Attributes/attributes.yaml
```

---

## Routing Table — User Intent to Skill

When a user asks about configurator files, use these keywords to route to the correct skill:

| User mentions... | Route to skill |
|-----------------|----------------|
| website, store, store view, store group, multi-store | `configurator-websites` |
| config, system config, setting, path/value, core_config_data | `configurator-config` |
| sequence, order number, invoice prefix, increment | `configurator-sequence` |
| attribute, dropdown, swatch, select, product property, EAV | `configurator-attributes` |
| attribute set, attribute group | `configurator-attribute-sets` |
| category, subcategory, category tree, landing page | `configurator-categories` |
| product, SKU, simple product, configurable, CSV import | `configurator-products` |
| CMS block, static block, block content | `configurator-cms` |
| CMS page, page content, homepage | `configurator-cms` |
| widget, widget instance | `configurator-widgets` |
| media, image download, file download | `configurator-media` |
| admin role, ACL, permissions, resource | `configurator-admin` |
| admin user, backend user | `configurator-admin` |
| customer group, tax class | `configurator-customer-groups` |
| customer import, customer CSV | `configurator-customers` |
| customer attribute, customer EAV | `configurator-customer-attributes` |
| tax rate, VAT rate, tax percentage | `configurator-tax` |
| tax rule, tax class | `configurator-tax` |
| API integration, OAuth, REST API | `configurator-api-integrations` |
| product link, related product, upsell, cross-sell | `configurator-product-links` |
| URL rewrite, redirect, 301, 302 | `configurator-rewrites` |
| review, rating, star rating | `configurator-review-rating` |
| catalog price rule, discount, promotion | `configurator-catalog-price-rules` |
| SQL, raw query, database | `configurator-sql` |
| shipping rate, table rate, shipping cost | `configurator-shipping-table-rates` |
| tiered price, quantity discount, tier pricing | `configurator-tiered-prices` |
| order status, order state | `configurator-order-statuses` |

---

## Cross-Component Workflows

Common multi-component tasks and the skills involved:

### "Add a new product attribute (e.g., hover_image)"
1. **`configurator-attributes`** — create the attribute definition (input type, label, scope, etc.)
2. **`configurator-attribute-sets`** — assign the attribute to an attribute set group
3. **`configurator-products`** — add the attribute code as a CSV column header

### "Set up a new store/website"
1. **`configurator-websites`** — define website, store group, store views
2. **`configurator-config`** — add website/store-scoped configuration
3. **`configurator-sequence`** — customise order number sequences per store

### "Add a CMS landing page to a category"
1. **`configurator-cms`** — create the CMS block with HTML content
2. **`configurator-categories`** — set `landing_page` to the block identifier and `display_mode` to `PRODUCTS_AND_PAGE`

### "Set up tax for a new country"
1. **`configurator-tax`** — add tax rates (CSV) then tax rules linking rates to customer/product classes

### "Import products with images"
1. **`configurator-media`** — download/copy image files to `pub/media/`
2. **`configurator-products`** — reference image paths in the CSV columns

---

## Clarifying Questions Protocol

When a user asks to create or modify configurator files, always clarify:

1. **Which environment?** — Is this for all environments (global sources) or a specific one (local, staging, production)?
2. **Create new or amend?** — Should we create a new YAML/CSV file or add entries to an existing one?
3. **Configurator directory path?** — Where are the configurator files located? (Default: `configurator/` sibling to `app/`)
4. **master.yaml location?** — Is there an existing `master.yaml`? Does it need updating?

---

## File Format Reference

| Format | Components | Notes |
|--------|-----------|-------|
| YAML | Most components | Parsed by Symfony YAML parser |
| CSV | products, customers, taxrates, taxrules, rewrites, tiered_prices | First row = headers |
| JSON | Any (auto-detected) | Less common, but supported |
| HTML | Block/page content files | Referenced via `source` key in blocks/pages YAML |
| SQL | sql component | Referenced via path in sql.yaml |

Source files can be **local paths** (relative to Magento root) or **remote URLs** (fetched via HTTP).
