mysql -uroot -e "CREATE DATABASE IF NOT EXISTS magentodb;"
mysql -uroot -e "GRANT ALL ON magentodb.* TO '%'@'%' IDENTIFIED BY 'magento' WITH GRANT OPTION; FLUSH PRIVILEGES;"
gunzip < features/data/magento-demo.sql.gz | mysql magentodb -uroot