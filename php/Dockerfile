FROM php:7.3-alpine

# Set timezone to Asia/Tokyo
RUN apk add --no-cache tzdata && \
    cp /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && \
    echo "Asia/Tokyo" > /etc/timezone && \
    apk del tzdata && \
    rm -rf /var/cache/apk/*

# Install necessary dependencies and PHP extensions
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/freetype2 --with-jpeg-dir=/usr/include \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring exif \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Copy php.ini configuration
COPY php.ini-development /usr/local/etc/php/php.ini

# Set the working directory
WORKDIR /work

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "/work/public"]
