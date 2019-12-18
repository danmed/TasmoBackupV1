FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
COPY *.db /var/www/html/
RUN rm /var/www/html/index.html
CMD php /var/www/html/install.php
