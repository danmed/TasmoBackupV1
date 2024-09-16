ARG BUILD_FROM=php:fpm-alpine
ARG BUILD_FROM_PREFIX
FROM ${BUILD_FROM}
#${BUILD_FROM_PREFIX}

ARG BUILD_ARCH
ARG QEMU_ARCH
WORKDIR /

COPY install.sh qemu-${QEMU_ARCH}-static* /usr/bin/
COPY --chown=www-data . /var/www/html/
COPY supervisord.conf /etc/supervisor/conf.d/
COPY bin /usr/local/bin/



ENV NGINX_SENDFILE=off \
    NGINX_WORKER_PROCESSES=1 \
    NGINX_WORKER_CONNECTIONS=200 \
    NGINX_KEEPALIVE_TIMEOUT=65 \
    NGINX_PROXY_TIMEOUT=2000 \
    NGINX_LOG_NOTFOUND=off \
    NGINX_LOG_ACCESS=off \
    NGINX_GZIP_STATIC=on

RUN echo "Supervisor Install" \
&& apk add --no-cache --purge -uU supervisor inotify-tools \
&& 	rm -rf /var/cache/apk/* /tmp/* \
&& chmod +x /usr/local/bin/*

RUN echo "Nginx Install" \
&& apk add --no-cache --purge -uU nginx \
&& mkdir -p /var/www/html/data

COPY --chown=www-data nginx /etc/nginx/
COPY --chown=www-data data /var/www/html


RUN echo "Start" \
 && cd /var/www/html/resources \
 && gzip -k -9 *.js \
 && gzip -k -9 *.css \
 && chown www-data:www-data *.gz \
 && mkdir -p /etc/nginx/vhost \
 && ln -s /usr/local/bin/php /usr/bin/php \
 && rm -f /var/www/html/install.sh /var/www/html/qemu-*-static \
 && printf '        location ~* \.css$$ {\n\
            expires 1h;\n\
            log_not_found off;\n\
            access_log off;\n\
        }\n\
\n\
        location ~* \.js$$ {\n\
            expires 1h;\n\
            log_not_found off;\n\
            access_log off;\n\
        }\n\
\n\
        location ~* \.(jpg|jpeg|gif|png|ico)$$ {\n\
            expires 1d;\n\
            log_not_found off;\n\
            access_log off;\n\
        }\n\
\n\
        # Deny access to . files, for security\n\
        location ~ /\. {\n\
            log_not_found off;\n\
            access_log off;\n\
            deny all;\n\
        } \n\
' > /etc/nginx/vhost/server \
 && chmod 755 /usr/bin/install.sh \
 && echo '8  *  *  *  *    /usr/bin/wget -O - "http://localhost/backupall.php?docker=true" 1>/dev/null 2>/dev/null ' > /etc/crontabs/root \
 && echo "Done"

CMD [ "/usr/bin/install.sh", "/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf" ]

ARG BUILD_DATE
ARG BUILD_REF
ARG BUILD_VERSION
LABEL maintainer="Errol Sancaktar" \
  Description="Manage Tasmota scheduled backups and restores." \
  ForkedFrom="danmed/TasmoBackupV1:master" \
  io.hass.name="TasmoBackup" \
  io.hass.description="Manage Tasmota scheduled backups and restores." \
  io.hass.arch="${BUILD_ARCH}" \
  io.hass.type="addon" \
  io.hass.version=${BUILD_VERSION} \
  org.label-schema.schema-version="1.0" \
  org.label-schema.build-date="${BUILD_DATE}" \
  org.label-schema.name="TasmoBackup" \
  org.label-schema.description="Manage Tasmota scheduled backups and restores." \
  org.label-schema.url="https://github.com/errolsancaktar/TasmoBackupV1" \
  org.label-schema.usage="https://github.com/errolsancaktar/TasmoBackupV1/tree/master/README.md" \
  org.label-schema.vcs-url="https://github.com/errolsancaktar/TasmoBackupV1" \
  org.label-schema.vcs-ref="${BUILD_REF}" \
  org.label-schema.version="${BUILD_VERSION}"
