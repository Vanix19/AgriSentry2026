FROM php:8.4-apache

# --------------------------------------------------
# Install system dependencies
# --------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------
# Install PHP extensions required by Laravel
# --------------------------------------------------
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# --------------------------------------------------
# Enable Apache rewrite
# --------------------------------------------------
RUN a2enmod rewrite

# --------------------------------------------------
# Install Composer
# --------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --------------------------------------------------
# Laravel application directory
# --------------------------------------------------
WORKDIR /var/www/html

# --------------------------------------------------
# Copy Laravel application
# --------------------------------------------------
COPY . /var/www/html/

# --------------------------------------------------
# Install Laravel dependencies
# --------------------------------------------------
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# --------------------------------------------------
# Install frontend dependencies
# --------------------------------------------------
RUN npm install \
    && npm run build

# --------------------------------------------------
# Laravel public directory
# --------------------------------------------------
RUN sed -i \
    's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' \
    /etc/apache2/sites-available/000-default.conf

# --------------------------------------------------
# Explicit Apache configuration for Laravel
# --------------------------------------------------
RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/laravel.conf

RUN a2enconf laravel

# --------------------------------------------------
# Laravel storage permissions
# --------------------------------------------------
RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

# --------------------------------------------------
# Render port
# --------------------------------------------------
RUN sed -i 's/Listen 80/Listen 10000/' \
    /etc/apache2/ports.conf

RUN sed -i 's/:80>/:10000>/' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

# --------------------------------------------------
# Start Apache
# --------------------------------------------------
CMD ["apache2-foreground"]
