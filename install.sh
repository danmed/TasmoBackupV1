#!/bin/sh

cp /var/www/html/tasmobackup.db /var/www/html/data/tasmobackup.db
cp /var/www/html/config.inc.php.example /var/www/html/data/config.inc.php
mkdir /var/www/html/data/backups
chmod 777 /var/www/html/data/backups
