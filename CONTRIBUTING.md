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

### Making use of the sample data

If you symlink the `Samples\Components` directory 1 level outside the Magento Root and symlink the `Samples\master.yaml` file in your Magento's `app/etc` directory.


### CLI Based Logging Styles
@todo

## Pull Requests

Before submitting a pull request there are a few things that you should ensure. These include:

1) Running Code Sniffer / Mess Detector / Duplicate Code Detector test. 
```
php vendor/bin/phpcs --standard=PSR2 vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console/ vendor/ctidigital/magento2-configurator/Test/ vendor/ctidigital/magento2-configurator/Api/ vendor/ctidigital/magento2-configurator/Component/ vendor/ctidigital/magento2-configurator/Exception/
php vendor/bin/phpmd vendor/ctidigital/magento2-configurator/Model/,vendor/ctidigital/magento2-configurator/Console/,vendor/ctidigital/magento2-configurator/Test/,vendor/ctidigital/magento2-configurator/Api/,vendor/ctidigital/magento2-configurator/Component/,vendor/ctidigital/magento2-configurator/Exception/ text cleancode,codesize,controversial,design,naming,unusedcode
php vendor/bin/phpcpd vendor/ctidigital/magento2-configurator/Model/ vendor/ctidigital/magento2-configurator/Console vendor/ctidigital/magento2-configurator/Test/ vendor/ctidigital/magento2-configurator/Api/ vendor/ctidigital/magento2-configurator/Component/ vendor/ctidigital/magento2-configurator/Exception/
```
2) Include PHP Unit tests. If you're developing a new component, it is important that the component fits the framework by extending `ComponentAbstractTestCase` within `Test\Unit\Component`.
Then you would have to run the unit tests to ensure there are no failures.
```
php vendor/bin/phpunit --coverage-clover build/logs/clover.xml vendor/ctidigital/magento2-configurator/Test/Unit/
```

3) Include Samples. If you have developed/modified a component and it requires a change in the sample data to test the new feature/change this should be included with its corresponding component in the `Samples` directory and `master.yaml` should be updated to reflect this.

4) Run configurator. If you've developed a component ensure it actually works with configurator and shows appropriate CLI based logging to feedback to the user.
```
bin/magento configurator:run --env="<environment>" --components="<your component>"
```