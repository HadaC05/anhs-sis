# Install only the PHP packages required in production.
FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
# Laravel's Composer hook calls `php artisan package:discover`, but this stage
# intentionally has only composer files. Run discovery after the application
# has been copied into the final image below.
RUN composer install --no-dev --no-scripts --no-interaction --no-progress --prefer-dist --optimize-autoloader

# Build browser assets separately so the production image does not need Node.js.
# Flux's stylesheet is imported from Composer's vendor directory, so copy that
# directory into this stage before Vite compiles app.css.
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=dependencies /app/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        nginx \
        gettext \
        freetype \
        libjpeg-turbo \
        libpng \
        libzip \
        oniguruma \
        postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd mbstring pdo_mysql pdo_pgsql zip opcache \
    && apk del .build-deps \
    && mkdir -p /run/nginx

COPY . .
COPY --from=dependencies /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY docker/nginx/default.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/start-container /usr/local/bin/start-container

RUN php artisan package:discover --ansi \
    && chmod +x /usr/local/bin/start-container \
    && chown -R www-data:www-data storage bootstrap/cache

ENV PORT=10000
EXPOSE 10000

CMD ["/usr/local/bin/start-container"]
