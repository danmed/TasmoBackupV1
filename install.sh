#!/bin/sh

if [ ! -e /var/www/html/data/config.inc.php ]; then
    if [ -e /data/options.json ]; then
        if [ -d /var/www/html/data ]; then
            rm /var/www/html/data
        else
            ln -s /data /var/www/html/data
        fi
        for keyval in $(grep -E '": [^\{]' /data/options.json | sed -e 's/: /=/' -e "s/\(\,\)$//"); do
            eval export $keyval
        done
    else
        mkdir --mode=775 /var/www/html/data
        chown www-data:www-data /var/www/html/data
    fi
    cp /var/www/html/config.inc.php.example /var/www/html/data/config.inc.php
fi

if [ -e /data/options.json ]; then
    USER=$(stat -c '%U' /data)
    if [ "${USER}" != "www-data" ]; then
        chown -R www-data:www-data /data
    fi
fi    

if [ ! -e /var/www/html/data/backups ]; then
    mkdir --mode=775 /var/www/html/data/backups
    chown www-data:www-data /var/www/html/data/backups
else
    USER=$(stat -c '%U' /var/www/html/data/backups)
    if [ "${USER}" != "www-data" ]; then
        chown -R www-data:www-data /var/www/html/data
    fi
fi

if [ ! -z $TZ ]; then
    sed -i "s|UTC|${TZ}|" /etc/php7/conf.d/custom.ini
fi

sed -i "s|mysqlserver|$MYSQL_SERVER|g" /var/www/html/data/config.inc.php
sed -i "s|mysqlusername|$MYSQL_USERNAME|g" /var/www/html/data/config.inc.php
sed -i "s|mysqlpassword|$MYSQL_PASSWORD|g" /var/www/html/data/config.inc.php
sed -i "s|sqlite|$DBTYPE|g" /var/www/html/data/config.inc.php
sed -i "s|data/tasmobackup|$DBNAME|g" /var/www/html/data/config.inc.php

#if [ "$DBTYPE" == "mysql" ]; then
#  docker-php-ext-install mysqli pdo_mysql
#fi

su -l -p www-data -s /usr/bin/php /var/www/html/upgrade.php 1>/dev/null

/usr/sbin/crond -l 9

#su nobody -s /bin/sh -c "$@"
exec "$@"
