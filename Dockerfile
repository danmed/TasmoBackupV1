ARG BUILD_FROM=patrickdk/docker-php-nginx:latest
ARG BUILD_FROM_PREFIX
FROM ${BUILD_FROM}${BUILD_FROM_PREFIX}
MAINTAINER Dan Medhurst (danmed@gmail.com)
ARG ARCH
ARG QEMU_ARCH
ARG BUILD_DATE
ARG VCS_REF
ARG BUILD_VERSION
WORKDIR /
COPY install.sh qemu-${QEMU_ARCH}-static* /usr/bin/
COPY --chown=www-data . /var/www/html/
RUN echo "Start" \
 && rm -f /var/www/html/install.sh /var/www/html/qemu-*-static \
 && chmod 755 /usr/bin/install.sh \
 && echo '8  *  *  *  *    /usr/bin/wget -O - "http://localhost/backupall.php?docker=true" 1>/dev/null 2>/dev/null ' > /etc/crontabs/root \
 && echo "Done"
CMD [ "/usr/bin/install.sh", "/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf" ]

LABEL maintainer="Dan Medhurst (danmed@gmail.com)" \
  org.label-schema.schema-version="1.0" \
  org.label-schema.build-date="${BUILD_DATE}" \
  org.label-schema.name="danmed/tasmobackupv1" \
  org.label-schema.description="Tasmota Backup" \
  org.label-schema.url="https://github.com/danmed/TasmoBackupV1" \
  org.label-schema.vcs-url="https://github.com/danmed/TasmoBackupV1" \
  org.label-schema.vcs-ref="${VCS_REF}" \
  org.label-schema.version="${BUILD_VERSION}"
