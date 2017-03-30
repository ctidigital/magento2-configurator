#!/bin/sh

echo Setting up Magento

# Update Composer
composer selfupdate

# Disable xdebug for performance
echo '' > ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini

# Create Database
mysql -e 'CREATE DATABASE IF NOT EXISTS configurator;'