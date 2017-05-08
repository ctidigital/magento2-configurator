cd magento2

echo List Configurator Command Test...
php bin/magento configurator:list

echo Run Configurator Command Test...
php bin/magento configurator:run --env local

echo Run Configurator Command Test in verbose...
php bin/magento configurator:run --env local -v