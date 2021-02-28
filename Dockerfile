ARG BUILD_FROM=patrickdk/docker-php-nginx:latest
ARG BUILD_FROM_PREFIX
FROM ${BUILD_FROM}${BUILD_FROM_PREFIX}

ARG BUILD_ARCH
ARG QEMU_ARCH
WORKDIR /

COPY install.sh qemu-${QEMU_ARCH}-static* /usr/bin/
COPY --chown=www-data . /var/www/html/

ENV NGINX_SENDFILE=off \
    NGINX_WORKER_PROCESSES=1 \
    NGINX_WORKER_CONNECTIONS=200 \
    NGINX_KEEPALIVE_TIMEOUT=65 \
    NGINX_PROXY_TIMEOUT=2000 \
    NGINX_LOG_NOTFOUND=off \
    NGINX_LOG_ACCESS=off \
    NGINX_EXPIRES_CSS=1h \
    NGINX_EXPIRES_IMAGES=1d

RUN echo "Start" \
 && rm -f /var/www/html/install.sh /var/www/html/qemu-*-static \
 && chmod 755 /usr/bin/install.sh \
 && echo '8  *  *  *  *    /usr/bin/wget -O - "http://localhost/backupall.php?docker=true" 1>/dev/null 2>/dev/null ' > /etc/crontabs/root \
 && echo "Done"

CMD [ "/usr/bin/install.sh", "/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf" ]

ARG BUILD_DATE
ARG BUILD_REF
ARG BUILD_VERSION

LABEL maintainer="Dan Medhurst (danmed@gmail.com)" \
  Description="Manage Tasmota scheduled backups and restores." \
  ForkedFrom="" \
  io.hass.name="TasmoBackup" \
  io.hass.description="Manage Tasmota scheduled backups and restores." \
  io.hass.arch="${BUILD_ARCH}" \
  io.hass.type="addon" \
  io.hass.version=${BUILD_VERSION} \
  org.label-schema.schema-version="1.0" \
  org.label-schema.build-date="${BUILD_DATE}" \
  org.label-schema.name="TasmoBackup" \
  org.label-schema.description="Manage Tasmota scheduled backups and restores." \
  org.label-schema.url="https://github.com/danmed/TasmoBackupV1" \
  org.label-schema.usage="https://github.com/danmed/TasmoBackupV1/tree/master/README.md" \
  org.label-schema.vcs-url="https://github.com/danmed/TasmoBackupV1" \
  org.label-schema.vcs-ref="${BUILD_REF}" \
  org.label-schema.version="${BUILD_VERSION}"
