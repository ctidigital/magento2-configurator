---
name: configurator-order-statuses
description: Use when the user wants to create custom order statuses via the configurator. Triggers on mentions of order statuses, order states, custom order status.
---

# Order Statuses Component

Alias: `order_statuses`

Creates custom order statuses and assigns them to order states.

## YAML Schema

```yaml
order_statuses:
  - state: <state_code>
    statuses:
      - code: <status_code>
        name: <status_label>
```

- Root key: `order_statuses` (array)
- Each entry maps a Magento order state to one or more custom statuses
- `state`: one of the built-in Magento order states
- `statuses`: array of status objects with `code` (machine name) and `name` (display label)

### Valid Magento Order States

- `new`
- `pending_payment`
- `processing`
- `complete`
- `closed`
- `canceled`
- `holded`

## Sample Configuration

```yaml
order_statuses:
  - state: processing
    statuses:
      - code: processing_custom1
        name: Processing Custom 1
      - code: processing_custom2
        name: Processing Custom 2
  - state: new
    statuses:
      - code: new_custom1
        name: New Custom 1
```

## Notes

- Status codes should be lowercase with underscores (snake_case)
- Multiple custom statuses can be assigned to the same state
- Custom statuses appear in the Magento admin order management interface
- States are fixed by Magento core; only statuses within them can be customised
