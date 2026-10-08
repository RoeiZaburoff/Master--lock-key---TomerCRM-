FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libzip-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j2 pdo_mysql gd zip mbstring \
    && rm -rf /var/lib/apt/lists/* && a2enmod rewrite
COPY . /var/www/crm
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
RUN find /var/www/crm -type d -exec chmod 0755 {} + \
    && find /var/www/crm -type f -exec chmod 0644 {} + \
    && mkdir -p /var/www/crm/storage/logos /var/www/crm/storage/inventory /var/www/crm/storage/pdf \
    && chown -R www-data:www-data /var/www/crm/storage
