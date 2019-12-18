FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
COPY *.db /var/www/html/
COPY *.sh /var/www/html/
RUN rm /var/www/html/index.html
RUN chmod 777 /var/www/html/install.sh
CMD /var/www/html/install.sh
