FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
RUN rm /var/www/html/index.html
RUN mkdir -p /var/www/html/data
RUN chmod 777 /var/www/html/data
RUN mkdir -p /var/www/html/data/backups
RUN chmod 777 /var/www/html/data/backups
