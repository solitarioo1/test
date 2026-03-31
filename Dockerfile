# ========================================
# Stage 1: Build assets (Node.js)
# ========================================
FROM node:20 AS builder

WORKDIR /app

COPY package.json ./
RUN npm install

COPY gulpfile.js ./
COPY src/ ./src/

RUN npm run build

# ========================================
# Stage 2: PHP + Apache (production)
# ========================================
FROM php:8.2-apache

# Variables de build desde EasyPanel
ARG APP_ENV=production
ARG APP_DEBUG=false
ARG APP_URL=http://localhost
ARG DB_HOST
ARG DB_PORT=3306
ARG DB_NAME
ARG DB_USER
ARG DB_PASSWORD
ARG DB_CHARSET=utf8mb4
ARG CHATBOT_DB_NAME
ARG CHATBOT_DB_USER
ARG CHATBOT_DB_PASSWORD
ARG GEMINI_API_KEY
ARG MAIL_HOST
ARG MAIL_PORT=587
ARG MAIL_USERNAME
ARG MAIL_PASSWORD
ARG MAIL_FROM_ADDRESS
ARG MAIL_FROM_NAME=IntiSmart

# Convertir a variables de entorno
ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    APP_URL=${APP_URL} \
    DB_HOST=${DB_HOST} \
    DB_PORT=${DB_PORT} \
    DB_NAME=${DB_NAME} \
    DB_USER=${DB_USER} \
    DB_PASSWORD=${DB_PASSWORD} \
    DB_CHARSET=${DB_CHARSET} \
    CHATBOT_DB_NAME=${CHATBOT_DB_NAME} \
    CHATBOT_DB_USER=${CHATBOT_DB_USER} \
    CHATBOT_DB_PASSWORD=${CHATBOT_DB_PASSWORD} \
    GEMINI_API_KEY=${GEMINI_API_KEY} \
    MAIL_HOST=${MAIL_HOST} \
    MAIL_PORT=${MAIL_PORT} \
    MAIL_USERNAME=${MAIL_USERNAME} \
    MAIL_PASSWORD=${MAIL_PASSWORD} \
    MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS} \
    MAIL_FROM_NAME=${MAIL_FROM_NAME}

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite headers deflate expires

# Copy Apache VirtualHost config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy project files
COPY . .

# Copy compiled assets from builder stage
COPY --from=builder /app/public/build ./public/build

# Install PHP dependencies (no dev, optimized)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy and set entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
