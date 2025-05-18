# Use the official PHP + Apache image
FROM php:8.2-apache

# Install required system dependencies and Composer
RUN apt-get update && apt-get install -y unzip curl \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html/

# Copy all application code to Apache web root
COPY . .

# Install PHP dependencies
RUN composer install

# Expose port 80
EXPOSE 80
