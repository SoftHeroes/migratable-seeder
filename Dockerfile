FROM php:7.2-alpine

COPY ./scripts /usr/local/bin

# Install Composer and non-root user
# Install Xdebug extension for PHP
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && adduser -D -u 1000 composer \
    && apk --no-cache add --virtual build-deps g++ autoconf make \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del build-deps \
    && chmod +x -R /usr/local/bin

USER composer

VOLUME ["/usr/local/src"]

WORKDIR /usr/local/src

ENTRYPOINT ["entrypoint.sh"]