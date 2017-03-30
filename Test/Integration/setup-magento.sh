#!/bin/sh

ls

tar cvzf configurator.tar.gz .

echo Setting up Magento

echo Disabling xdebug for performance
echo '' > ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini

echo Creating configurator database
mysql -e 'CREATE DATABASE IF NOT EXISTS configurator;'

echo Install Magento
git clone https://github.com/magento/magento2
cd magento2

ls

composer install

ls

#php bin/magento setup:install --admin-email "test@test.com" --admin-firstname "CTI" --admin-lastname "Test" --admin-password "password123" --admin-user "admin" --backend-frontname admin --base-url "http://configurator.dev" --db-host 127.0.0.1 --db-name configurator --db-user root --session-save files --use-rewrites 1 --use-secure 0 -vvv

mkdir -p vendor/ctidigital/magento2-configurator

mv ../configurator.tar.gz vendor/ctidigital/magento2-configurator/.

ls

cd vendor/ctidigital/magento2-configurator/
tar -xvf configurator.tar.gz


ls