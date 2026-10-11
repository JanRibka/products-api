FROM php:8.5-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy Xdebug configuration
COPY xdebug.ini "${PHP_INI_DIR}/conf.d"

# Set working directory
WORKDIR /var/www/html/api

# Pre-create Xdebug log and ensure permissions
RUN touch /tmp/xdebug.log && chmod 777 /tmp/xdebug.log

# Expose port 9003
EXPOSE 9003

CMD ["php-fpm"]
