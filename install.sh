#!/bin/sh

if [ ! -f /var/www/html/data/config.inc.php ]; then
    cp /var/www/html/config.inc.php.example /var/www/html/data/config.inc.php
fi

if [ ! -f var/www/html/data/backups ]; then
    mkdir /var/www/html/data/backups
    chmod 777 /var/www/html/data/backups
fi

sed -i "s/mysqlserver/$MYSQL_SERVER/g" /var/www/html/data/config.inc.php
sed -i "s/mysqlusername/$MYSQL_USERNAME/g" /var/www/html/data/config.inc.php
sed -i "s/mysqlpassword/$MYSQL_PASSWORD/g" /var/www/html/data/config.inc.php
