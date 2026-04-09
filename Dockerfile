# =============================================================================
# Stage 1 – Frontend build (only used in prod target)
# =============================================================================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN mkdir -p node_modules/.vite-temp && chmod -R 777 node_modules/.vite-temp
RUN npm run build

# =============================================================================
# Stage 2 – PHP base (shared by dev + prod)
# =============================================================================
FROM php:8.3-apache AS base

RUN apt-get update && apt-get install -y \
        git curl unzip \
        libzip-dev libicu-dev libonig-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libxml2-dev libexif-dev libmagickwand-dev \
        ghostscript poppler-utils imagemagick \
        libreoffice-writer-nogui \
        libreoffice-calc-nogui \
        libreoffice-impress-nogui \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql bcmath gd pcntl intl zip exif \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

EXPOSE 80

# =============================================================================
# Stage 3 – Development
# (code is mounted via volume, so no COPY here)
# =============================================================================
FROM base AS dev

# Install Node.js in dev image too (needed for `npm run dev` inside container)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY .docker/start.dev.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]

# =============================================================================
# Stage 4 – Production
# =============================================================================
FROM base AS prod

COPY . /var/www/html

# Copy pre-built frontend assets from Stage 1
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install PHP dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

COPY .docker/start.prod.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
