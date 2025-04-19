FROM httpd:2.4-alpine

RUN apk add --no-cache apache2 apache2-utils

# custom httpd.conf
COPY httpd.conf /usr/local/apache2/conf/httpd.conf

RUN mkdir -p /usr/local/apache2/modules /var/www/html/public