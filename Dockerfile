FROM php:8.3-apache

# Install required system packages and PHP PDO MySQL
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    curl \
    unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set DocumentRoot to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage || true
RUN chmod -R 775 /var/www/html/storage || true

EXPOSE 80
CMD ["apache2-foreground"]

