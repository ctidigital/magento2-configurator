---
name: configurator-review-rating
description: Use when the user wants to create product review ratings via the configurator. Triggers on mentions of review ratings, star ratings, product ratings.
---

# Review Rating Component

Alias: `review_rating`

Creates and configures product review rating attributes (e.g. Quality, Value, Price).

## YAML Schema

```yaml
review_rating:
  <rating_code>:
    is_active: <0|1>
    position: <integer>
    stores:
      - <store_code>
```

- Root key: `review_rating`
- Each child key is a rating code (e.g. `Quality`, `Value`, `Price`)
- Properties:
  - `is_active`: `1` to enable, `0` to disable
  - `position`: sort order position for display
  - `stores`: array of store view codes where the rating is visible

## Sample Configuration

```yaml
review_rating:
  Quality:
    is_active: 1
    position: 0
    stores:
      - default
  Value:
    is_active: 1
    position: 1
    stores:
      - default
  Price:
    is_active: 1
    position: 2
    stores:
      - default
```

## Notes

- Rating codes serve as both the identifier and the display label
- The `stores` array controls which store views the rating appears on
- Ratings are used in product reviews and are separate from product attributes
