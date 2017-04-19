# Magento 2 Configurator

[![Build Status](https://travis-ci.org/ctidigital/magento2-configurator.svg?branch=develop)](https://travis-ci.org/ctidigital/magento2-configurator)


A Magento module initially created by [CTI Digital] to create and maintain database variables using files. This module aims to bring the following benefits to a Magento developer's work flow:

  - Install Magento from scratch with important database based configuration ready.
  - Share and collaborate configuration with other colleagues using your own versioning system.
  - Keep versions of your configurations using your own versioning system.
  - Split your configuration based on the environment you're developing on.

If you're interested about finding out more about the background of the configurator, watch this lightning talk by [Rick Steckles] at Mage Titans in Manchester on [YouTube].

This is a work in progress and by no means for use with production environments (and probably not even development environments either just yet).

## Testing Locally For Development
If you are contributing the module, please run the following commands to stand the best chance with Travis CI liking your code.
These test include PHP Code Sniffer, PHP Mess Detector, PHP Copy and Paste Detector, PHP Unit
```
php vendor/bin/phpcs --standard=PSR2 vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console/ vendor/ctidigital/magento2-configurator/Test/ vendor/ctidigital/magento2-configurator/Helper/
php vendor/bin/phpmd vendor/ctidigital/magento2-configurator/Model/,vendor/ctidigital/magento2-configurator/Console/,vendor/ctidigital/magento2-configurator/Test/,vendor/ctidigital/magento2-configurator/Helper/ text cleancode,codesize,controversial,design,naming,unusedcode
php vendor/bin/phpcpd vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console vendor/ctidigital/magento2-configurator/Test/ vendor/ctidigital/magento2-configurator/Helper/
php vendor/bin/phpunit --coverage-clover build/logs/clover.xml vendor/ctidigital/magento2-configurator/Test/Unit/
```

If you're developing a new component, please ensure you have your corresponding unit test which extends `ComponentAbstractTestCase` as that will test that your component has the required functions.
Do also include sample files with your component that works 

## Getting Started
1. Create a `master.yaml` file in `<mage_root>/app/etc/`. (see `Samples/master.yaml`)
2. Enable Modules `CtiDigital_Configurator`,`FireGento_FastSimpleImport`.
3. Run `bin/magento configurator:run --env="<environment>"`

### Usage

* Listing available components `bin/magento configurator:list`
* Running individual components `bin/magento configurator:run --env="<environment>" --component="config"`
* Extra logs `bin/magento configurator:run --env="<environment>" -v`

## Roadmap for components to do

| Component                 | Code Written       | Tests Written      | Sample Files       |
|---------------------------|--------------------|--------------------|--------------------|
| Websites                  | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| System Configuration      | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Categories                | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Products                  | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Attributes                | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Blocks                    | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Admin Roles               | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Admin Users               | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Pages                     | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Widgets                   | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Customer Groups           | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Media                     | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Tax Rules                 | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| API Integrations          | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Tax Rates                 | :white_check_mark: | :grey_exclamation: | :white_check_mark: |
| Attribute Sets            | :x:                | :x:                | :x:                |
| Customers                 | :x:                | :x:                | :x:                |
| Related Products          | :x:                | :x:                | :x:                |
| SQL                       | :x:                | :x:                | :x:                |
| Shipping Table Rates      | :x:                | :x:                | :x:                |
| Catalog Price Rules       | :x:                | :x:                | :x:                |
| Shopping Cart Price Rules | :x:                | :x:                | :x:                |
| Rewrites                  | :x:                | :x:                | :x:                |
| Orders                    | :x:                | :x:                | :x:                |

License
----

MIT


[CTI Digital]:http://www.ctidigital.com/
[YouTube]:https://www.youtube.com/watch?v=u9zHaX8G5_0
[Rick Steckles]:https://twitter.com/rick_steckles
