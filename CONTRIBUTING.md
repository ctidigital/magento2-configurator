# Contributing

Thank you for considering a contribution to magento2-configurator!

## Setting Up for Development

The simplest approach is to install the extension as a path repository inside a working Magento 2
instance, which lets you test your changes against a real store.

```bash
# In your Magento root's composer.json, add a path repository pointing to your fork:
composer config repositories.configurator path /path/to/your/fork/magento2-configurator
composer require ctidigital/magento2-configurator:@dev
```

`vendor/ctidigital/magento2-configurator` will then be a symlink to your fork — edits are live
immediately, no reinstall needed.

To install the dev tools (phpcs, phpmd, phpunit) without a Magento installation, configure the
public [Mage-OS mirror] to resolve `magento/magento-coding-standard` without credentials, then
run composer from inside the extension directory:

```bash
composer config --global repositories.mage-os composer https://mirror.mage-os.org/
cd /path/to/your/fork/magento2-configurator
composer install
```

## Before Submitting a Pull Request

All checks below are run automatically by GitHub Actions on every PR. Passing them locally before
you push saves round-trips.

### 1 — Unit tests

```bash
composer test
```

This runs 175 unit tests via PHPUnit. No Magento installation is required — the suite uses a
standalone bootstrap and class stubs. Alternatively, without a `composer install`:

```bash
curl -sL https://phar.phpunit.de/phpunit-10.phar -o phpunit.phar
php phpunit.phar --configuration phpunit.xml
```

**New components must have a corresponding unit test.** Place it in `Test/Unit/Component/`
following the naming and structure of the existing test files (e.g. `BlocksTest.php`).

### 2 — Static analysis

```bash
composer cs       # PHP_CodeSniffer — Magento2 standard, errors only
composer md       # PHP Mess Detector
composer analyse  # Both together
```

Current baseline: **0 phpcs errors, 0 phpmd violations.** The CI build will fail if either
tool reports a new issue against your changes.

### 3 — Sample files

If your change affects accepted YAML fields, column names, or data format for any component,
update the corresponding file in `Samples/Components/` and ensure `Samples/master.yaml` reflects
it. Sample files are the contract between the extension and its operators.

### 4 — Manual smoke test

Run the affected component against a real Magento instance to confirm the CLI output and
behaviour are correct:

```bash
bin/magento configurator:run --env="<environment>" --component="<your-component>"
bin/magento configurator:run --env="<environment>" --component="<your-component>" -v
```

## Coding Standards

- PHP 8.3+, `declare(strict_types=1)` in every file.
- Constructor property promotion with `readonly` for injected dependencies.
- Native types on all properties, parameters, and return values.
- Use Magento service contracts (Repository / API interfaces) in preference to concrete
  models wherever they exist.
- No `ObjectManager` usage in components.
- Suppressed PHPMD warnings (`@SuppressWarnings`) must be specific rule names
  (e.g. `PHPMD.CyclomaticComplexity`), not blanket `@SuppressWarnings(PHPMD)`.

## Pull Request Checklist

- [ ] `composer test` passes (175 tests, 0 errors)
- [ ] `composer analyse` passes (0 phpcs errors, 0 phpmd violations)
- [ ] New/changed component has a unit test
- [ ] Sample file updated if YAML format changed
- [ ] Manually verified against a Magento instance

[Mage-OS mirror]: https://mage-os.org/
