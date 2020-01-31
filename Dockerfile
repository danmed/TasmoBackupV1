FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
COPY *.png /var/www/html/
COPY *.sh /var/www/html/
COPY *.example /var/www/html/
COPY resources /var/www/html/
COPY lib /var/www/html/
RUN rm /var/www/html/index.html \
 && mv /var/www/html/install.sh /etc/my_runonce/install.sh \
 && mv /var/www/html/runalways.sh /etc/my_runalways/runalways.sh \
 && chmod 777 /etc/my_runalways/runalways.sh \
 && chmod 777 /etc/my_runonce/install.sh \
 && echo 'PassEnv DBTYPE'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv DBNAME'   >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_SERVER'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_USERNAME'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_PASSWORD'  >> /etc/apache2/conf-enabled/expose-env.conf


