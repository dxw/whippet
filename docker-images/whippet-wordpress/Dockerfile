FROM php:apache

RUN apt-get update && apt-get dist-upgrade -y

# mysqli
RUN docker-php-ext-install mysqli
# gd
RUN apt-get install -y --no-install-recommends libpng12-dev && apt-get clean -y
RUN docker-php-ext-install gd

# mod_rewrite
RUN ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled

# install wordpress
RUN curl --silent https://wordpress.org/latest.tar.gz > /usr/src/latest.tar.gz \
    && tar -C /usr/src -xzf /usr/src/latest.tar.gz \
    && rm -rf /var/www/html /usr/src/wordpress/wp-content \
    && mv /usr/src/wordpress /var/www/html

COPY apache2.conf /etc/apache2/apache2.conf
COPY mu-plugins /usr/src/mu-plugins
COPY wp-config.php /var/www/html/wp-config.php
