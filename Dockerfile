# Use PHP 8.4 with FPM
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# Argumentos para UID e GID do usuário local
ARG USER_ID=1000
ARG GROUP_ID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    sqlite3 \
    libsqlite3-dev \
    sudo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_sqlite \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/laravel.conf /etc/nginx/sites-available/default

# Copy Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Criar um grupo com o mesmo GID do host e adicionar www-data a ele
RUN groupadd --force -g $GROUP_ID devgroup \
    && usermod -a -G devgroup www-data

# Criar um usuário não-root com o mesmo UID/GID do host
RUN useradd -u $USER_ID -g $GROUP_ID -m -s /bin/bash -G www-data,sudo devuser \
    && echo "devuser ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/devuser

# Copy application files
COPY --chown=devuser:devgroup . /var/www/html

# Set proper permissions
RUN find /var/www/html -type d -exec chmod 777 {} \; \
    && find /var/www/html -type f -exec chmod 666 {} \; \
    && chmod -R 777 /var/www/html/storage \
    && chmod -R 777 /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/artisan \
    && find /var/www/html/docker/scripts -type f -name "*.sh" -exec chmod +x {} \;

# Create SQLite database directory and supervisor logs directory
RUN mkdir -p /var/www/html/database \
    && touch /var/www/html/database/database.sqlite \
    && chown -R devuser:devgroup /var/www/html/database \
    && chmod 777 /var/www/html/database \
    && chmod 666 /var/www/html/database/database.sqlite \
    && mkdir -p /var/log/supervisor \
    && touch /var/log/supervisor/supervisord.log \
    && chmod -R 777 /var/log/supervisor

# Install PHP dependencies
USER devuser
RUN composer install --optimize-autoloader --no-interaction

# Switch back to root for supervisord
USER root

# Expose port 80
EXPOSE 80

# Configure entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
