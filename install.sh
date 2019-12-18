#!/bin/sh

if [ ! -f /var/www/html/data/tasmobackup.db ]; then
    cp /var/www/html/tasmobackup.db /var/www/html/data/tasmobackup.db
fi

if [ ! -f /var/www/html/data/config.inc.php ]; then
    cp /var/www/html/config.inc.php.example /var/www/html/data/config.inc.php
fi

if [ ! -f var/www/html/data/backups ]; then
    mkdir /var/www/html/data/backups
    chmod 777 /var/www/html/data/backups
fi
