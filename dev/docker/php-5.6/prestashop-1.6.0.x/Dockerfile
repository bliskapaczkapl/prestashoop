FROM prestashop/prestashop:1.6.0.14
MAINTAINER Mateusz Koszutowski <mkoszutowski@divante.pl>

ENV prestashop_path /var/www/html

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    wget \
    curl \
    git \
    apt-utils \
    sudo \
    mysql-client

# Upadate PHP config (for translations)
COPY dev/docker/php/php.ini /usr/local/etc/php/php.ini

# Copy latest version of Bliskapaczka module
COPY modules ${prestashop_path}/modules

# Change dir permisions
RUN chmod 777 ${prestashop_path}/log
RUN chmod 777 ${prestashop_path}/config/
RUN chmod -R 777 ${prestashop_path}/cache/
RUN chmod -R 777 ${prestashop_path}/img/
RUN chmod -R 777 ${prestashop_path}/mails/
RUN chmod -R 777 ${prestashop_path}/modules/
RUN chmod -R 777 ${prestashop_path}/override/
RUN chmod 777 ${prestashop_path}/themes/default-bootstrap/lang/
RUN chmod 777 ${prestashop_path}/themes/default-bootstrap/pdf/lang/
RUN chmod 777 ${prestashop_path}/themes/default-bootstrap/cache/
RUN chmod -R 777 ${prestashop_path}/translations/
RUN chmod 777 ${prestashop_path}/upload/
RUN chmod 777 ${prestashop_path}/download/

RUN mv ${prestashop_path}/admin ${prestashop_path}/admin6666ukv7e

RUN sed '/#LogLevel info ssl:warn/a        LogLevel debug' /etc/apache2/sites-available/000-default.conf

COPY dev/docker/run /opt/run

EXPOSE 80

CMD bash /opt/run