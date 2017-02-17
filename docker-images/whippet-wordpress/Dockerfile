FROM php:apache

# mysqli
RUN docker-php-ext-install mysqli
# gd
RUN apt-get update && apt-get install -y --no-install-recommends libpng12-dev && rm -r /var/lib/apt/lists/*
RUN docker-php-ext-install gd

# mod_rewrite
RUN a2enmod rewrite

# install git
RUN apt-get update && apt-get install -y --no-install-recommends git && rm -r /var/lib/apt/lists/*

# install wordpress
RUN git clone https://github.com/dxw/WordPress-1.git /usr/src/wordpress \
    && git -C /usr/src/wordpress checkout feature/inline-js \
    && rm -rf /var/www/html /usr/src/wordpress/wp-content \
    && mv /usr/src/wordpress /var/www/html

COPY php.ini /usr/local/etc/php/
COPY wordpress.conf /etc/apache2/sites-enabled/
COPY mu-plugins /usr/src/mu-plugins/
COPY wp-config.php /var/www/html/wp-config.php
