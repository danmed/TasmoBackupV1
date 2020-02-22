ARG BUILD_FROM=php:7.2-apache
ARG BUILD_FROM_PREFIX
FROM ${BUILD_FROM_PREFIX}${BUILD_FROM}
MAINTAINER Dan Medhurst (danmed@gmail.com)
ARG ARCH
ARG QEMU_ARCH
ARG BUILD_DATE
ARG VCS_REF
ARG BUILD_VERSION
COPY install.sh qemu-${QEMU_ARCH}-static* /usr/bin/
COPY . /var/www/html/
RUN echo "Start" \
 && rm -f install.sh qemu-*-static \
 && chmod 755 /usr/bin/install.sh \
 && echo 'PassEnv DBTYPE'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv DBNAME'   >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_SERVER'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_USERNAME'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo 'PassEnv MYSQL_PASSWORD'  >> /etc/apache2/conf-enabled/expose-env.conf \
 && echo "Done"
CMD [ "/usr/bin/install.sh", "apache2-foreground" ]

LABEL maintainer="Dan Medhurst (danmed@gmail.com)" \
  org.label-schema.schema-version="1.0" \
  org.label-schema.build-date="${BUILD_DATE}" \
  org.label-schema.name="danmed/tasmobackupv1" \
  org.label-schema.description="Tasmota Backup" \
  org.label-schema.url="https://github.com/danmed/TasmoBackupV1" \
  org.label-schema.vcs-url="https://github.com/danmed/TasmoBackupV1" \
  org.label-schema.vcs-ref="${VCS_REF}" \
  org.label-schema.version="${BUILD_VERSION}"
