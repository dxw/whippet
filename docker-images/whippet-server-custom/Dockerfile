FROM thedxw/whippet-server:tomdxw

# Install some tools to help when using "whippet server console"
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends vim-nox less && apt-get clean
RUN COMPOSER_HOME=/usr/local/lib/composer composer global require wp-cli/wp-cli

# Make wp-cli "just work"
RUN echo '#!/bin/sh' >> /usr/local/bin/wp
RUN echo 'exec /usr/local/lib/composer/vendor/bin/wp --allow-root $@' >> /usr/local/bin/wp
RUN chmod 755 /usr/local/bin/wp
RUN mkdir /root/.wp-cli
RUN echo 'path: /root/.cache/whippet/wordpresses/latest/' >> /root/.wp-cli/config.yml

COPY . /usr/src
CMD whippet-server -i 0.0.0.0 -p 8000 --siteurl=http://localhost:8000 --config=/usr/src/whippet-wp-config.php /usr/src/app/wp-content
