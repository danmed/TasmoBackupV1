FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
COPY *.db /var/www/html/
RUN rm /var/www/html/index.html
RUN chmod 777 install.sh
CMD /install.sh
