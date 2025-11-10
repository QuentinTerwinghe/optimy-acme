FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    librabbitmq-dev \
    libssh-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    sockets

# Install AMQP extension for RabbitMQ
RUN pecl install amqp && docker-php-ext-enable amqp

# Install XDebug for debugging
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Copy Apache configuration
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/apache/apache2.conf /etc/apache2/apache2.conf

# Copy PHP configuration
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application files
COPY ./www /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create entrypoint script
COPY .docker/apache/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ARG XDEBUG_CLIENT_PORT
ENV XDEBUG_MODE=debug
ENV XDEBUG_CLIENT_PORT=${XDEBUG_CLIENT_PORT:-9013}
RUN echo "xdebug.client_port=\${XDEBUG_CLIENT_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
