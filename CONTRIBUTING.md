# How to contribute

Firstly, thank you for considering contributing!

When creating the original configurator for Magento 1, we knew it was something that can be valuable to the Magento Communnity.
Now what Magento 2 is out, it is something we want to do again however this time, we want to get this it out at the earliest stage possible for the Magento community to also build from the ground up.

## The Ideal Setup

Although your setup preferences may differ, the easiest way to get started is by including this project via composer into an fully setup Magento 2 install.

```
composer require ctidigital/magento2-configurator
```

Fork the project to your own GitHub Account and then set the remote URLs to point to your fork as detailed here: https://help.github.com/articles/changing-a-remote-s-url/.

## Pull Requests

Before submitting a pull request there are a few things that you should ensure. These include:

1) Running Code Sniffer / Mess Detector / Duplicate Code Detector test.
```
php vendor/bin/phpcs --standard=PSR2 vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console/ vendor/ctidigital/magento2-configurator/Test/
php vendor/bin/phpmd vendor/ctidigital/magento2-configurator/Model/,vendor/ctidigital/magento2-configurator/Console/,vendor/ctidigital/magento2-configurator/Test/ text cleancode,codesize,controversial,design,naming,unusedcode
php vendor/bin/phpcpd vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console vendor/ctidigital/magento2-configurator/Test/
```
2) @Todo