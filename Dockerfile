FROM nimmis/apache-php7
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY *.php /var/www/html/
COPY *.png /var/www/html/
COPY *.sh /var/www/html/
COPY *.example /var/www/html/
RUN mkdir /var/www/html/resources
RUN cp resources/ /var/www/html/resources/
RUN rm /var/www/html/index.html
RUN mv /var/www/html/install.sh /etc/my_runonce/install.sh
RUN mv /var/www/html/runalways.sh /etc/my_runalways/runalways.sh
RUN chmod 777 /etc/my_runalways/runalways.sh
RUN chmod 777 /etc/my_runonce/install.sh
RUN echo 'PassEnv DBTYPE'  >> /etc/apache2/conf-enabled/expose-env.conf
RUN echo 'PassEnv DBNAME'   >> /etc/apache2/conf-enabled/expose-env.conf
RUN echo 'PassEnv MYSQL_SERVER'  >> /etc/apache2/conf-enabled/expose-env.conf
RUN echo 'PassEnv MYSQL_USERNAME'  >> /etc/apache2/conf-enabled/expose-env.conf
RUN echo 'PassEnv MYSQL_PASSWORD'  >> /etc/apache2/conf-enabled/expose-env.conf


