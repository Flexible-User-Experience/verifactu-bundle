# Development image for the VerifactuBundle, defaults to PHP 8.4 (CI matrix supports 8.2 to 8.5)
ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli-alpine

# PHP extensions required by the bundle and its development tooling:
#   - gd (with FreeType & JPEG support): legal QR code PNG image generation (endroid/qr-code & khanamiryan/qrcode-detector-decoder)
#   - intl: localization support for Symfony components
#   - zip: faster Composer package installations
#   - xdebug: step debugging & code coverage reports (disabled by default, see XDEBUG_MODE)
RUN apk add --no-cache bash freetype git icu-libs libjpeg-turbo libpng libzip unzip \
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libzip-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN mv "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini" \
    && { \
        echo 'date.timezone = UTC'; \
        echo 'memory_limit = 1G'; \
    } > "${PHP_INI_DIR}/conf.d/zz-verifactu-bundle.ini" \
    && { \
        echo 'xdebug.client_host = host.docker.internal'; \
        echo 'xdebug.start_with_request = trigger'; \
    } >> "${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini"

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    XDEBUG_MODE=off

WORKDIR /app

CMD ["bash"]
