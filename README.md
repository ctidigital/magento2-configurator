# Magento 2 Configurator

[![Build Status](https://travis-ci.org/ctidigital/magento2-configurator.svg?branch=develop)](https://travis-ci.org/ctidigital/magento2-configurator)

This is a work in progress and by no means for use with production environments (and probably not even development environments either just yet).

## Testing Locally For Development

### PHP Code Sniffer
php vendor/bin/phpcs --standard=PSR2 vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console/ vendor/ctidigital/magento2-configurator/Test/

### PHP Mess Detector
php vendor/bin/phpmd vendor/ctidigital/magento2-configurator/Model/,vendor/ctidigital/magento2-configurator/Console/,vendor/ctidigital/magento2-configurator/Test/ text cleancode,codesize,controversial,design,naming,unusedcode

### PHP Copy and Paste Detector
php vendor/bin/phpcpd vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console vendor/ctidigital/magento2-configurator/Test/

### PHP Unit
php vendor/bin/phpunit --coverage-clover build/logs/clover.xml vendor/ctidigital/magento2-configurator/Test/Unit/