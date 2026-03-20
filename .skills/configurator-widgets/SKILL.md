---
name: configurator-widgets
description: Use when the user wants to create or modify CMS widget instances via the configurator. Triggers on mentions of widgets, widget instances, widget parameters.
---

# Widgets Component

- **Alias**: `widgets`
- **PHP class**: `CtiDigital\Configurator\Component\Widgets`
- **Sample**: `Samples/Components/Widgets/widgets.yaml`

## YAML Schema

The top level is a YAML array (list). Each entry defines one widget instance.

```yaml
-
  instance_type: <fully-qualified-block-class>
  title: <widget title>
  theme: <Vendor/theme>
  stores:
    - <store_code>
    - <store_code>
  parameters:
    <param_key>: <param_value>
    <param_key>: <param_value>
```

## Fields

| Field           | Required | Description |
|-----------------|----------|-------------|
| `instance_type` | Yes      | Fully qualified PHP class of the widget block, e.g. `Magento\Cms\Block\Widget\Page\Link` or `Magento\Cms\Block\Widget\Block`. This determines what the widget renders. |
| `title`         | Yes      | Display title. Used together with `instance_type` to identify an existing widget for updates. |
| `theme`         | Yes      | Theme code in `Vendor/theme` format (e.g. `Magento/blank`, `Magento/luma`). Resolved to a theme ID internally. |
| `stores`        | Yes      | Array of store view codes. Converted to a comma-separated list of store IDs internally. |
| `parameters`    | No       | Key-value map of widget parameters. Serialized to JSON and stored as `widget_parameters`. |

## How Parameters Work

The `parameters` map is serialized via `SerializerInterface` (JSON) and stored in the `widget_parameters` column. Parameter keys and values depend on the widget's `instance_type`. Common examples:

- For `Magento\Cms\Block\Widget\Page\Link`: `anchor_text`, `title`, `page_id`
- For `Magento\Cms\Block\Widget\Block`: `block_id`

```yaml
parameters:
  anchor_text: Click Here
  title: Link Title
  page_id: 4
```

Note: Parameters that reference entity IDs (like `page_id` or `block_id`) require the referenced entity to already exist with that ID.

## Widget Identification

Widgets are identified by the combination of `instance_type` + `title`. If a widget with the same type and title already exists, it will be updated. Otherwise a new widget instance is created.

## Full Example

```yaml
-
  instance_type: Magento\Cms\Block\Widget\Page\Link
  title: Test Link
  theme: Magento/blank
  stores:
    - default
    - usa_en_us
  parameters:
    anchor_text: Anchor Test
    title: Anchor Title
    page_id: 4
-
  instance_type: Magento\Cms\Block\Widget\Block
  title: Test Block
  theme: Magento/blank
  stores:
    - default
  parameters:
    block_id: 13
```

## Processing Behavior

- The component loops through the widget collection to find matches by `instance_type` and `title`.
- The `theme` value (e.g. `Magento/blank`) is resolved to a numeric `theme_id` by querying the theme collection.
- The `stores` array of store codes is resolved to store IDs and stored as a comma-separated `store_ids` string.
- Widget saving is wrapped in `appState->emulateAreaCode(AREA_FRONTEND, ...)` because `Widget\Instance::save()` resolves theme directory paths that require a frontend area context.
- Changes are only saved if any data value differs from the existing widget.
