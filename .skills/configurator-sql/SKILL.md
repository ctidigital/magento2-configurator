---
name: configurator-sql
description: Use when the user wants to execute raw SQL files via the configurator. Triggers on mentions of SQL execution, raw queries, database scripts, SQL import.
---

# SQL Component

Alias: `sql`

Executes raw SQL files against the Magento database. Use with caution -- this should typically run last in the component sequence.

## YAML Schema

```yaml
sql:
  <name>: <path/to/file.sql>
```

- Root key: `sql`
- Each entry is a map of a logical name to a file path (relative to the Magento root)
- SQL files can contain multiple statements

## Sample Configuration

```yaml
sql:
  sitemap: ../configurator/Sql/sitemap.sql
```

## Notes

- SQL is executed directly against the database with no rollback mechanism
- Should be ordered last in the configurator component list to avoid conflicts with other components
- Paths are relative to the Magento root directory
- Each SQL file can contain multiple semicolon-separated statements
