---
name: configurator-cms
description: Use when the user wants to create or modify CMS blocks or CMS pages via the configurator. Triggers on mentions of CMS blocks, static blocks, CMS pages, page content, homepage, block content, HTML content.
---

# CMS Blocks and Pages Components

Two components share a similar structure for managing Magento CMS content through YAML configuration.

## Blocks Component

- **Alias**: `blocks`
- **PHP class**: `CtiDigital\Configurator\Component\Blocks`
- **Sample**: `Samples/Components/Blocks/blocks.yaml`

### YAML Schema

The root keys are CMS block **identifiers** (the URL-key-style string Magento uses to reference blocks). Each identifier contains a `block` array with one or more entries.

```yaml
<identifier>:
  block:
    -
      source: <relative-path-to-html-file>   # OR omit source and set content inline (not typical for blocks)
      title: <block title>
      is_active: 1                            # 1 = enabled, 0 = disabled
      stores:                                 # optional; omit to assign to All Store Views (store ID 0)
        - <store_code>
        - <store_code>
```

### Key Fields

| Field      | Required | Description |
|------------|----------|-------------|
| `source`   | Yes*     | Path to an HTML file relative to Magento root (BP). The file contents become the block's `content`. |
| `title`    | Yes      | Display title of the block. |
| `is_active`| Yes      | `1` for active, `0` for inactive. |
| `stores`   | No       | Array of store view codes. When omitted, block is assigned to store ID 0 (All Store Views). |

*Either `source` or inline `content` must be provided. `source` is the standard approach.

### Store-Specific Blocks

To create store-specific variations of the same block identifier, add multiple entries under the `block` array, each with its own `stores` list and a different `source` file:

```yaml
certain_stores_identifier:
  block:
    -
      source: ../configurator/Blocks/uk.html
      title: UK Block
      is_active: 1
      stores:
        - default
    -
      source: ../configurator/Blocks/us.html
      title: US Block
      is_active: 1
      stores:
        - usa_en_us
```

### How Source Files Work

The `source` value is resolved as `BP . '/' . $value` where BP is the Magento base path. HTML files typically live in a `configurator/Blocks/` directory relative to the Magento root. The file contents are read and stored as the block's `content` field.

---

## Pages Component

- **Alias**: `pages`
- **PHP class**: `CtiDigital\Configurator\Component\Pages`
- **Sample**: `Samples/Components/Pages/pages.yaml`

### YAML Schema

The root keys are CMS page **identifiers**. Each identifier contains a `page` array with one or more entries.

```yaml
<identifier>:
  page:
    -
      source: <relative-path-to-html-file>   # file-based content
      title: <page title>                     # REQUIRED
      page_layout: 1column                    # defaults to "empty" if omitted
      is_active: 1                            # defaults to "1" if omitted
      stores:                                 # optional; omit for All Store Views
        - <store_code>
      # Optional meta fields:
      meta_title:
      meta_keywords:
      meta_description:
      content_heading:
      sort_order:
      layout_update_xml:
      custom_theme:
      custom_root_template:
      custom_layout_update_xml:
      custom_theme_from:
      custom_theme_to:
```

### Key Fields

| Field          | Required | Default   | Description |
|----------------|----------|-----------|-------------|
| `title`        | Yes      | --        | Page title. The only strictly required field. |
| `source`       | No*      | --        | Path to HTML file (relative to BP). Read and stored as `content`. |
| `content`      | No*      | --        | Inline HTML content string. Used when `source` is not set. |
| `page_layout`  | No       | `empty`   | Layout handle: `1column`, `2columns-left`, `2columns-right`, `3columns`, `empty`. |
| `is_active`    | No       | `1`       | `1` for active, `0` for inactive. |
| `stores`       | No       | All (0)   | Array of store view codes for store-specific pages. |

*Provide either `source` (file-based) or `content` (inline), not both. If neither is given the page will have empty content.

### Inline Content vs File-Based Content

File-based (recommended for substantial HTML):
```yaml
my_page:
  page:
    -
      source: ../configurator/Pages/mypage.html
      title: My Page
      page_layout: 1column
      is_active: 1
```

Inline content (suitable for simple content):
```yaml
inline_content_page:
  page:
    -
      content: Page Content
      title: Inline Content Page
```

### Store-Specific Pages

Same pattern as blocks -- multiple entries under the `page` array, each with different `stores`:

```yaml
certain_stores_identifier:
  page:
    -
      source: ../configurator/Pages/uk.html
      title: UK Page
      is_active: 1
      page_layout: 1column
      stores:
        - default
    -
      source: ../configurator/Pages/us.html
      title: US Page
      page_layout: 1column
      is_active: 1
      stores:
        - usa_en_us
```

---

## Processing Behavior (Both Components)

- Both components check for existing entities by identifier (and store scope for blocks).
- If an existing entity is found with the same identifier, it is updated rather than duplicated.
- Changes are only saved if the data has actually changed (dirty check).
- The `source` key is transparently converted to `content` during processing -- it never reaches the database.
- The `stores` key is consumed for store assignment and is not passed as entity data.
