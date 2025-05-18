# Use the official PHP + Apache image
FROM php:8.2-apache

# Install required system dependencies and Composer
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    libpq-dev \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# 🛠 Install PostgreSQL PDO driver
RUN docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /var/www/html/

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install

# Expose port 80
EXPOSE 80
