FROM thedxw/whippet-server:tomdxw

COPY . /usr/src
CMD whippet-server -i 0.0.0.0 -p 8000 --siteurl=http://localhost:8000 --config=/usr/src/whippet-wp-config.php /usr/src/app/wp-content
