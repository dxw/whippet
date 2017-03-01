FROM php:apache

# vi
RUN apt-get update && apt-get install -y --no-install-recommends vim-tiny && rm -r /var/lib/apt/lists/*

# mysqli
RUN docker-php-ext-install mysqli
# gd
RUN apt-get update && apt-get install -y --no-install-recommends libpng12-dev && rm -r /var/lib/apt/lists/*
RUN docker-php-ext-install gd

# mod_rewrite
RUN a2enmod rewrite

# install wordpress
RUN curl --silent https://wordpress.org/latest.tar.gz > /usr/src/latest.tar.gz \
    && tar -C /usr/src -xzf /usr/src/latest.tar.gz \
    && rm -rf /var/www/html /usr/src/wordpress/wp-content \
    && mv /usr/src/wordpress /var/www/html

COPY php.ini /usr/local/etc/php/
COPY wordpress.conf /etc/apache2/sites-enabled/
COPY mu-plugins /usr/src/mu-plugins/
COPY wp-config.php /var/www/html/wp-config.php
