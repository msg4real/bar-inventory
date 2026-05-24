FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    openssl curl git \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite gd \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod ssl rewrite headers remoteip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html-image
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction
COPY . .

COPY docker/apache.conf /etc/apache2/sites-available/bar.conf
RUN a2dissite 000-default && a2ensite bar

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 443
HEALTHCHECK --interval=30s --timeout=10s --start-period=20s --retries=3 \
    CMD curl -fsk http://localhost/api/health || exit 1

ENTRYPOINT ["/entrypoint.sh"]
