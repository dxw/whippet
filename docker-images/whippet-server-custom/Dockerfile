FROM thedxw/whippet-server:tomdxw

# Install some tools to help when using "whippet server console"
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends vim-nox && apt-get clean

COPY . /usr/src
CMD whippet-server -i 0.0.0.0 -p 8000 --siteurl=http://localhost:8000 --config=/usr/src/whippet-wp-config.php /usr/src/app/wp-content
