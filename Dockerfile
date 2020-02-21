ARG BUILD_FROM=php:7.2-apache
ARG BUILD_FROM_PREFIX
ARG ARCH
ARG QEMU_ARCH
FROM ${BUILD_FROM_PREFIX}${BUILD_FROM}
MAINTAINER Dan Medhurst (danmed@gmail.com)
COPY install.sh qemu-${ARCH}-static /usr/bin/
COPY . /var/www/html/
RUN echo "Start" \
 && rm -f install.sh qemu* \
 && chmod 755 /usr/bin/install.sh \
 && echo 'PassEnv DBTYPE'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv DBNAME'   >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_SERVER'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_USERNAME'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_PASSWORD'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo "Done"
CMD [ "/usr/bin/install.sh", "apache2-foreground" ]
