FROM php:8.4-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends iputils-ping \
    && docker-php-ext-install mysqli \
    && rm -rf /var/lib/apt/lists/*
COPY docker/php.ini /usr/local/etc/php/conf.d/crap2.ini
COPY docker/apache.conf /etc/apache2/conf-available/crap2.conf
RUN a2enconf crap2
COPY app/ /var/www/html/
COPY docker/worker.sh /usr/local/bin/crap-worker
RUN chmod +x /usr/local/bin/crap-worker
WORKDIR /var/www/html
