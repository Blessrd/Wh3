FROM php:8.2-apache

WORKDIR /var/www/html

# Dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        zip \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        gd \
    && rm -rf /var/lib/apt/lists/*

# Configuración PHP
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Apache
RUN a2enmod rewrite

# Cambiar DocumentRoot de Apache a Laravel /public
RUN sed -ri \
    -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# Permitir .htaccess
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar proyecto
COPY . .

# Instalar dependencias Laravel
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Crear directorios necesarios
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Permisos
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

EXPOSE 80