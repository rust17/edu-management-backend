FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx

# Clean apt cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Set directory permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Install project dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy Nginx configuration file
COPY docker/nginx/default.conf /etc/nginx/sites-enabled/default

# Copy PHP-FPM configuration
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Expose port
EXPOSE 80

# Copy startup script
COPY docker/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

# Start services
CMD ["/usr/local/bin/init.sh"]
