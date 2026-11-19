FROM php:8.4-apache-bookworm

# The official PHP image already builds the standard Laravel extensions.
# Check the exact extensions used by this application at build time.
RUN php -r 'foreach (["curl","dom","fileinfo","mbstring","openssl","pdo_sqlite","tokenizer","xml","xmlwriter"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "Missing PHP extension: ".$e.PHP_EOL); exit(1); } } if (!in_array("argon2id", password_algos(), true)) exit(1);'
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV PORT=80 APP_ENV=production APP_DEBUG=false APP_NAME="The Parlour" \
    DB_CONNECTION=sqlite DB_DATABASE=/var/lib/parlour/parlour.sqlite \
    SESSION_DRIVER=database SESSION_ENCRYPT=true SESSION_LIFETIME=30 \
    SESSION_EXPIRE_ON_CLOSE=true SESSION_SECURE_COOKIE=true SESSION_SAME_SITE=lax \
    CACHE_STORE=database HASH_DRIVER=argon2id LOG_CHANNEL=stderr
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /var/lib/parlour \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev \
    && ln -s /var/lib/parlour/app.env .env \
    && chown -R www-data:www-data storage bootstrap/cache \
    && a2enmod rewrite \
    && chmod +x docker/start
RUN printf '%s\n' 'Listen ${PORT}' > /etc/apache2/ports.conf
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
ENTRYPOINT ["/var/www/html/docker/start"]
CMD ["apache2-foreground"]
