# Magento 2 Configurator

[![Build Status](https://travis-ci.org/ctidigital/magento2-configurator.svg?branch=develop)](https://travis-ci.org/ctidigital/magento2-configurator)

This is a work in progress and by no means for use with production environments (and probably not even development environments either just yet).

## Testing Locally For Development
If you are contributing the module, please run the following commands to stand the best chance with Travis CI liking your code.
These test include PHP Code Sniffer, PHP Mess Detector, PHP Copy and Paste Detector, PHP Unit
```
php vendor/bin/phpcs --standard=PSR2 vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console/ vendor/ctidigital/magento2-configurator/Test/
php vendor/bin/phpmd vendor/ctidigital/magento2-configurator/Model/,vendor/ctidigital/magento2-configurator/Console/,vendor/ctidigital/magento2-configurator/Test/ text cleancode,codesize,controversial,design,naming,unusedcode
php vendor/bin/phpcpd vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console vendor/ctidigital/magento2-configurator/Test/
php vendor/bin/phpunit --coverage-clover build/logs/clover.xml vendor/ctidigital/magento2-configurator/Test/Unit/
```