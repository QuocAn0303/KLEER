FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    tar \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mariadb-client \
    busybox-extras \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        gd \
        zip \
        intl \
        opcache \
        bcmath \
        soap \
        exif \
        mbstring

# Install Redis extension
RUN pecl install redis-5.3.7 && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /bin/composer /usr/local/bin/composer

# Copy custom php.ini
COPY php.ini /usr/local/etc/php/conf.d/99-kleer.ini

# Clear cache
RUN rm -rf /tmp/* && apk cache clean

WORKDIR /var/www/html

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm8.2"]
