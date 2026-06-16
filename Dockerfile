FROM php:8.3-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /var/www/html

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN chmod +x /var/www/html/docker/start-apache.sh

EXPOSE 80

CMD ["/var/www/html/docker/start-apache.sh"]
