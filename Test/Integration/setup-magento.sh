#!/bin/sh

echo packaging configurator
tar czf configurator.tar.gz .

echo Setting up Magento

echo Disabling xdebug for performance
echo '' > ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini

echo Creating configurator database
mysql -e 'CREATE DATABASE IF NOT EXISTS configurator;'

echo Install Magento
git clone https://github.com/magento/magento2
cd magento2

git checkout tags/2.1.0 -b 2.1.0

composer require ctidigital/magento2-configurator
composer install

php bin/magento setup:install --admin-email "test@test.com" --admin-firstname "CTI" --admin-lastname "Test" --admin-password "password123" --admin-user "admin" --backend-frontname admin --base-url "http://configurator.dev" --db-host 127.0.0.1 --db-name configurator --db-user root --session-save files --use-rewrites 1 --use-secure 0 -vvv

echo Move configurator package into its own vendor directory
mv ../configurator.tar.gz vendor/ctidigital/magento2-configurator/.

cd vendor/ctidigital/magento2-configurator/

echo Extract configurator into the right place
tar -xf configurator.tar.gz

echo Go to app etc folder
cd ../../../app/etc


echo Symlink master.yaml folder
ln -s ../../../Samples/master.yml master.yaml

php bin/magento cache:flush
php bin/magento module:status
php bin/magento configurator:list