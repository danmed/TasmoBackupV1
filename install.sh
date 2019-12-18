#!/bin/sh

copy tasmobackup.db data/tasmobackup.db
copy config.inc.php.example data/config.inc.php
mkdir data/backups
chmod 777 data/backups
