# Use official PHP 8.0 image with Apache
FROM php:8.0-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy .env file (make sure you set it before building)
# COPY .env .env

# Generate Laravel key (optional if you run after container starts)
# RUN php artisan key:generate

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
