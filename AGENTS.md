# AGENTS.md — magento2-configurator Development Guide

This document provides guidance for AI agents working on the magento2-configurator PHP codebase.

For guidance on **populating configurator YAML/CSV data files**, see `CONFIGURATOR.md` and the `.skills/` directory instead.

---

## Project Overview

`ctidigital/magento2-configurator` is a Magento 2 extension that enables declarative, code-free configuration management. Store owners define their Magento setup in YAML/CSV files and run a single CLI command to apply it:

```bash
bin/magento configurator:run --env="local"
```

The extension processes 27 component types (websites, attributes, categories, products, CMS blocks, tax rates, etc.) in a defined execution order.

---

## Architecture

### Execution Flow

```
CLI Command (Console/Command/RunCommand.php)
  → Model/Processor.php        — reads master.yaml, iterates components
    → Service/ComponentRunner.php  — resolves sources, parses files, calls execute()
      → Component/<Name>.php       — processes the parsed data against Magento
```

### Key Interfaces

| Interface | Default Implementation | Purpose |
|-----------|----------------------|---------|
| `Api/ComponentInterface` | Each `Component/*.php` | `execute()`, `getAlias()`, `getDescription()` |
| `Api/ComponentListInterface` | `Component/ComponentList` | Ordered registry of all components |
| `Api/ComponentRunnerInterface` | `Service/ComponentRunner` | Resolves sources, handles environments |
| `Api/SourceDataParserInterface` | `Service/SourceDataParser` | Parses YAML/CSV/JSON files |
| `Api/MasterYamlReaderInterface` | `Service/MasterYamlReader` | Reads `app/etc/master.yaml` |
| `Api/LoggerInterface` | `Model/Logging` | Console logging with indentation levels |

### Component Registration

Components are registered via DI in `etc/di.xml` (lines 33-66). The order in `di.xml` determines execution order:

```xml
<type name="CtiDigital\Configurator\Api\ComponentListInterface">
    <arguments>
        <argument name="components" xsi:type="array">
            <item name="websites" xsi:type="object">...\Websites</item>
            <!-- ... 27 components total ... -->
        </argument>
    </arguments>
</type>
```

### File-Based Components

Most components receive parsed YAML/CSV data via `execute(mixed $data)`. Components that need raw file paths (e.g., for CSV import via Magento's native importers) implement `FileComponentInterface` instead — the runner passes the file path rather than parsed content.

---

## Code Conventions

### PHP Standards

- **PHP 8.3+** (verified on 8.3, 8.4 and 8.5) with `declare(strict_types=1)` in every file
- **Readonly constructor promotion**: `public function __construct(private readonly FooInterface $foo)`
- **No Zend_ classes** — use Laminas equivalents
- **String literals** over class constants for entity type IDs when it avoids importing large Magento classes (reduces PHPMD coupling)

### Static Analysis

Two tools enforce code quality:

**PHP_CodeSniffer** (Magento2 ruleset):
```bash
vendor/bin/phpcs --standard=Magento2 Api Component Console Exception Model Service
```

**PHPMD** (custom ruleset at `phpmd.xml`):
```bash
vendor/bin/phpmd Api,Component,Console,Exception,Model,Service text phpmd.xml
```

Key PHPMD thresholds:
- `CouplingBetweenObjects`: must be < 13 (max 12 dependencies per class)
- `CyclomaticComplexity`: must be <= 9
- `ExcessiveParameterList`: must be < 10

### Naming Conventions

- Component aliases: lowercase, underscored (e.g., `attribute_sets`, `customer_attributes`)
- Test files mirror source structure: `Component/Foo.php` → `Test/Unit/Component/FooTest.php`
- Factory classes: `<ClassName>Factory` (Magento auto-generates these)

---

## Testing

### Structure

```
Test/
  bootstrap.php     — autoloader setup
  stubs.php         — minimal class/interface stubs for PHPUnit mocking
  Unit/
    Component/      — one test per component
    Model/          — service/model tests
    Service/        — runner/parser tests
```

### Running Tests

```bash
# Local (no Docker needed)
vendor/bin/phpunit --configuration phpunit.xml --testdox

# Full static analysis + tests
composer analyse && composer test
```

### Stub Pattern

Since the extension runs outside Magento's full framework during tests, `Test/stubs.php` provides minimal class/interface definitions that PHPUnit needs to create mocks. When adding new dependencies:

1. Check if the class/interface already exists in `Test/stubs.php`
2. If not, add a minimal stub with only the methods your test needs to mock
3. Use `if (!class_exists(...))` guard to avoid conflicts if Magento classes are available
4. PHPUnit 10.5+ enforces return types — ensure mock callbacks return the correct type (e.g., `return $mock` for `static` return types)

### Test Conventions

- Use `onlyMethods()` for methods that exist on the stub/class
- Use `addMethods()` only for magic methods not defined in stubs
- Prefer `$this->createMock()` for interfaces, `$this->getMockBuilder()` for classes needing `onlyMethods`
- Capture `setData()` calls via `willReturnCallback()` for testing Magento model data setting

---

## Adding a New Component

1. **Create the component class** in `Component/` implementing `ComponentInterface`
2. **Register in `etc/di.xml`** under `ComponentListInterface` arguments — position determines execution order
3. **Wire `DriverInterface`** in `di.xml` if the component needs filesystem access (see existing examples)
4. **Add sample files** in `Samples/Components/<Name>/`
5. **Add sample entry** in `Samples/master.yaml`
6. **Create unit tests** in `Test/Unit/Component/`
7. **Add stubs** to `Test/stubs.php` for any new Magento classes used
8. **Run analysis**: `vendor/bin/phpcs` + `vendor/bin/phpmd` + `vendor/bin/phpunit`
9. **Create a skill** in `.skills/configurator-<name>/SKILL.md` documenting the YAML schema

---

## File Organisation

```
Api/            — interfaces (ComponentInterface, LoggerInterface, etc.)
Component/      — component implementations (one per Magento feature area)
  Processor/    — sub-processors (e.g., SqlSplitProcessor)
Console/        — CLI commands (configurator:run, configurator:list)
Exception/      — custom exceptions (ComponentException)
Model/          — processor, logging, extracted services (CmsBlockResolver, etc.)
Service/        — component runner, YAML reader, data parser
Samples/        — reference YAML/CSV files for all components
  master.yaml   — sample master configuration
  Components/   — per-component sample data files
Test/           — unit tests + stubs
etc/            — Magento module config (di.xml, module.xml)
.skills/        — AI skill definitions for configurator file generation
```

---

## Active Patches Caveat

When working with a Magento installation that uses `cweagans/composer-patches`, the live code in `vendor/` may differ from the source files in this repository. Common patched files include `Component/Categories.php`, `Component/Products.php`, `Component/TaxRates.php`, `Component/Widgets.php`, `Model/Logging.php`, and `Model/Processor.php`.

Always verify against the source repository, not the patched vendor copy.

---

## Useful Docker Commands

If the extension is bind-mounted into a Docker container (typical development setup):

```bash
# Run Magento CLI
docker exec -w /var/www/html <container> php bin/magento <command>

# Run configurator
docker exec -w /var/www/html <container> php bin/magento configurator:run --env="local"

# List available components
docker exec -w /var/www/html <container> php bin/magento configurator:list

# Regenerate DI after PHP class changes
docker exec -w /var/www/html <container> php bin/magento setup:di:compile

# Clear cache
docker exec -w /var/www/html <container> php bin/magento cache:flush
```
