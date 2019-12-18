FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
COPY *.db /var/www/html/
RUN rm /var/www/html/index.html
CMD copy tasmobackup.db data/tasmobackup.db
CMD copy config.inc.php.example data/config.inc.php
CMD mkdir data/backups
CMD chmod 777 data/backups
