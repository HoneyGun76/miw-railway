FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-cache

# Copy application code
COPY . .

# Ensure vendor directory is properly owned and regenerate autoloader
RUN chown -R www-data:www-data /app/vendor
RUN composer dump-autoload --optimize --no-cache

# Create upload directories
RUN mkdir -p /tmp/miw_uploads/documents \
    && mkdir -p /tmp/miw_uploads/payments \
    && mkdir -p /tmp/miw_uploads/photos \
    && mkdir -p /tmp/miw_uploads/cancellations \
    && chmod -R 777 /tmp/miw_uploads

# Optimize composer autoloader
RUN composer dump-autoload --optimize --no-cache

# Expose port
EXPOSE ${PORT:-3000}

# Start command
CMD ["php", "-S", "0.0.0.0:3000", "-t", "."]
