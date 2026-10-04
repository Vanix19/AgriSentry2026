
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
# Enable Apache URL rewriting
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
# Copy the Laravel project
# --------------------------------------------------
COPY . /var/www/html/

# --------------------------------------------------
# Install Laravel PHP dependencies
# --------------------------------------------------
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# --------------------------------------------------
# Install frontend dependencies and build assets
# --------------------------------------------------
RUN npm install \
    && npm run build

# --------------------------------------------------
# Configure Apache to serve Laravel's public folder
# --------------------------------------------------
RUN sed -i \
    's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' \
    /etc/apache2/sites-available/000-default.conf

RUN sed -i \
    's#<Directory /var/www/>#<Directory /var/www/html/public/>#' \
    /etc/apache2/apache2.conf

RUN sed -i \
    '/<Directory \/var\/www\/html\/public\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

RUN a2enmod rewrite

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
# Render uses port 10000
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
