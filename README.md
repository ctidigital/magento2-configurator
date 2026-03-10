# Magento 2 Configurator

[![Unit Tests](https://github.com/ctidigital/magento2-configurator/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/ctidigital/magento2-configurator/actions/workflows/unit-tests.yml)
[![Static Analysis](https://github.com/ctidigital/magento2-configurator/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/ctidigital/magento2-configurator/actions/workflows/static-analysis.yml)

A Magento module initially created by [CTI Digital] to create and maintain database variables using files. This module aims to bring the following benefits to a Magento developer's work flow:

  - Install Magento from scratch with important database based configuration ready.
  - Share and collaborate configuration with other colleagues using your own versioning system.
  - Keep versions of your configurations using your own versioning system.
  - Split your configuration based on the environment you're developing on.

If you're interested to find out more about the background of the configurator, watch this lightning talk by [Raj Chevli] at Mage Titans in Manchester on [YouTube].

## Getting Started

1. Create a `master.yaml` file in `<mage_root>/app/etc/`. (see `Samples/master.yaml`)
2. Enable modules: `bin/magento module:enable CtiDigital_Configurator FireGento_FastSimpleImport`
3. Run `bin/magento configurator:run --env="<environment>"`

### Usage

* List available components: `bin/magento configurator:list`
* Run individual components: `bin/magento configurator:run --env="<environment>" --component="config"`
* Verbose output: `bin/magento configurator:run --env="<environment>" -v`

## Development

### Requirements

- PHP 8.3+
- Composer 2

### Running tools locally

Install dev dependencies first. If `magento/magento-coding-standard` fails due to missing
`repo.magento.com` credentials, configure the public [Mage-OS mirror] globally:

```bash
composer config --global repositories.mage-os composer https://mirror.mage-os.org/
composer install
```

Once installed, use the composer scripts:

```bash
composer test      # PHPUnit — 175 unit tests, no Magento install needed
composer cs        # PHP_CodeSniffer (Magento2 standard, errors only)
composer md        # PHP Mess Detector
composer analyse   # phpcs + phpmd together
```

To run unit tests without a composer install (zero dependencies):

```bash
curl -sL https://phar.phpunit.de/phpunit-10.phar -o phpunit.phar
php phpunit.phar --configuration phpunit.xml
```

### Continuous Integration

Every push and pull request runs two GitHub Actions workflows automatically:

| Workflow | What it checks | PHP versions |
|---|---|---|
| **Unit Tests** | 175 PHPUnit tests via `phpunit.phar` — no Magento install, no credentials | 8.3, 8.4 |
| **Static Analysis** | phpcs (Magento2 standard) + phpmd + composer audit | 8.3, 8.4 |

All checks must pass before a PR can be merged.

## Integration Tests

- Configure your [Magento integration test environment](http://devdocs.magento.com/guides/v2.0/test/integration/integration_test_setup.html).
- Add the following to `dev/tests/integration/phpunit.xml.dist`:

```xml
<testsuite name="magento2-configurator">
    <directory>../../../vendor/ctidigital/magento2-configurator/Test/Integration</directory>
</testsuite>
```

- Run from `dev/tests/integration/`:

```bash
vendor/bin/phpunit --testsuite "magento2-configurator"
```

## Component Status

| Component                 | Code               | Unit Tests         | Sample Files       |
|---------------------------|--------------------|--------------------|--------------------|
| Websites                  | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| System Configuration      | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Categories                | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Products                  | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Attributes                | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Attribute Sets            | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Blocks                    | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Pages                     | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Admin Roles               | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Admin Users               | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Widgets                   | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Customer Groups           | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Customer Attributes       | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Customers                 | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Media                     | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Tax Rates                 | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Tax Rules                 | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| API Integrations          | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Order Statuses            | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Sequence Tables           | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Rewrites                  | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Review Ratings            | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Related Products          | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Up Sell Products          | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Cross Sell Products       | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| SQL                       | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Catalog Price Rules       | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Shipping Table Rates      | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| Tiered Prices             | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Shopping Cart Price Rules | :x:                | :x:                | :x:                |
| Orders                    | :x:                | :x:                | :x:                |

:white_check_mark: Done &nbsp; :grey_exclamation: Pending &nbsp; :x: Not started

## License

MIT

[CTI Digital]: http://www.ctidigital.com/
[YouTube]: https://www.youtube.com/watch?v=iFkhAzJl2k0
[Raj Chevli]: https://twitter.com/chevli
[Mage-OS mirror]: https://mage-os.org/
