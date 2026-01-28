# Use official PHP 8.2 FPM image
FROM php:8.2-fpm

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
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Clear Laravel caches
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Generate APP_KEY using environment variable if not set
# If APP_KEY exists in Render env, Laravel uses it automatically
RUN if [ -z "$APP_KEY" ]; then \
        php artisan key:generate --show | xargs -I {} echo "APP_KEY={}" >> .env ; \
    fi

# Set proper permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose port for Render
EXPOSE 10000

# Start Laravel with built-in server (Render maps $PORT)
CMD php artisan serve --host=0.0.0.0 --port=$PORT
