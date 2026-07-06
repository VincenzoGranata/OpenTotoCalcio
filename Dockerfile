# ── Stage 1: Backend Builder (Composer) ──────────────────────
FROM php:8.3-cli-bullseye AS backend-builder

WORKDIR /app

# Install build dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy only composer files for caching
COPY composer.json composer.lock* ./

# Install dependencies (without dev and scripts for now)
RUN composer install --no-interaction --no-plugins --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# ── Stage 2: Frontend Builder (Node + Gulp) ──────────────────
FROM node:22-bullseye AS frontend-builder

WORKDIR /app

# Enable Corepack for Yarn 4 (used by OSM)
RUN corepack enable && corepack prepare yarn@stable --activate

# Copy package files for caching
COPY package.json yarn.lock* .yarnrc.yml* ./

# Install dependencies
RUN yarn install

# Copy assets and gulpfile
COPY assets/src assets/src
COPY gulpfile.js .
COPY package.json .

# Copy necessary files from backend-builder (OSM Gulp needs some vendor files)
# For example: vendor/owasp/csrf-protector-php/js/csrfprotector.js
COPY --from=backend-builder /app/vendor /app/vendor

# Build assets
RUN npx gulp

# ── Stage 3: Runtime (Apache + PHP) ──────────────────────────
FROM php:8.3-apache-bullseye AS runtime

ENV DEBIAN_FRONTEND=noninteractive \
    APP_DIR="/var/www/html"

# Install runtime dependencies and PHP extensions
# These are kept in the final image, so we minimize them
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
    calendar \
    curl \
    exif \
    ftp \
    gd \
    gettext \
    intl \
    mbstring \
    mysqli \
    opcache \
    pdo_mysql \
    pcntl \
    shmop \
    soap \
    sockets \
    sysvmsg \
    sysvsem \
    sysvshm \
    xsl \
    zip \
    && a2enmod rewrite \
    && a2enmod headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/*

# Copy configuration (using the one from docker folder)
COPY docker/php.ini /usr/local/etc/php/php.ini

WORKDIR ${APP_DIR}

# Copy full source 
COPY . .

# Copy artifacts from builder stages to overwrite empty folders
COPY --from=backend-builder /app/vendor /var/www/html/vendor
COPY --from=frontend-builder /app/assets/dist /var/www/html/assets/dist

# Copy custom logos from local assets/dist/img to preserve them during build
COPY assets/dist/img /var/www/html/assets/dist/img

# Generate final autoloader using Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-scripts

# Permissions for the application
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/files /var/www/html/logs

EXPOSE 80

