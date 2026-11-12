# O composer.lock exige PHP >= 8.4 (Laravel 13 / Symfony 8)
FROM php:8.4-fpm

RUN useradd --system --no-log-init -u 1000 -g www-data www

# Instalador de extensões PHP
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions \
    bcmath \
    exif \
    gd \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    redis \
    zip

COPY .docker/php-fpm/php.ini /usr/local/etc/php/php.ini
COPY .docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

# Código + vendor (instalado no CI com --no-dev) + public/build (compilado no CI)
COPY --chown=www:www-data . /var/www/html

RUN rm -rf /var/www/html/.docker \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www:www-data /var/www/html/bootstrap/cache /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache /var/www/html/storage

USER www

WORKDIR /var/www/html

ENV TZ=America/Cuiaba

EXPOSE 9000

CMD ["php-fpm"]
