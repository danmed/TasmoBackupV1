#!/bin/sh

if [ ! -f /var/www/html/data/config.inc.php ]; then
    if [ -f /data/options.json ]; then
        rm /var/www/html/data
        ln -s /data /var/www/html/data
        for keyval in $(grep -E '": [^\{]' /data/options.json | sed -e 's/: /=/' -e "s/\(\,\)$//"); do
            eval export $keyval
        done
    else
        mkdir /var/www/html/data
        chmod 777 /vat/www/html/data
    fi
    cp /var/www/html/config.inc.php.example /var/www/html/data/config.inc.php
fi

if [ ! -f var/www/html/data/backups ]; then
    mkdir /var/www/html/data/backups
    chmod 777 /var/www/html/data/backups
fi

chmod 777 /var/www/html/data

sed -i "s/mysqlserver/$MYSQL_SERVER/g" /var/www/html/data/config.inc.php
sed -i "s/mysqlusername/$MYSQL_USERNAME/g" /var/www/html/data/config.inc.php
sed -i "s/mysqlpassword/$MYSQL_PASSWORD/g" /var/www/html/data/config.inc.php
sed -i "s/dbtype/$DBTYPE/g" /var/www/html/data/config.inc.php
sed -i "s#dbname#$DBNAME#g" /var/www/html/data/config.inc.php

#if [ "$DBTYPE" == "mysql" ]; then
#  docker-php-ext-install mysqli pdo_mysql
#fi

exec "$@"
