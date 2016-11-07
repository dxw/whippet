<?php

// Per-project configuration
if (file_exists('/usr/src/app/config/server.php')) {
    require('/usr/src/app/config/server.php');
}

// Per-host configuration (for setting WP_SITEURL to http://x.local etc)
if (file_exists('/usr/src/app/config/server-local.php')) {
    require('/usr/src/app/config/server-local.php');
}

// Populate globals from any env vars starting with WORDPRESS_
foreach ($_ENV as $k => $v) {
    $match = 'WORDPRESS_';
    if (substr($k, 0, strlen($match)) !== $match) {
        continue;
    }

    $const = substr($k, strlen($match));

    if (defined($const)) {
        continue;
    }

    define($const, $v);
}

// Password nerfing
if (!defined('DISABLE_PASSWORD_NERFING')) {
    function wp_check_password()
    {
        return true;
    }
}

// WP_DEBUG off by default
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// For some reason this is not being set correctly by default
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Set URLs if they aren't set already
if (!defined('WP_SITEURL')) {
    define('WP_SITEURL', 'http://localhost');
}
if (!defined('WP_HOME')) {
    define('WP_HOME', 'http://localhost');
}

// beanstalk
define('BEANSTALKD_HOST', getenv('BEANSTALK_PORT_11300_TCP_ADDR'));

// mu-plugins
define('WPMU_PLUGIN_DIR', '/usr/src/mu-plugins');

// Database
define('DB_HOST', getenv('MYSQL_PORT_3306_TCP_ADDR').':'.getenv('MYSQL_PORT_3306_TCP_PORT'));
define('DB_NAME', getenv('MYSQL_ENV_MYSQL_DATABASE'));
define('DB_USER', 'root');
define('DB_PASSWORD', getenv('MYSQL_ENV_MYSQL_ROOT_PASSWORD'));

// wp-config stuff

define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', true);
define('WP_ALLOW_MULTISITE', true);
define('FS_METHOD', 'direct');

define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

define('WPLANG', '');
define('ABSPATH', dirname(__FILE__) . '/');
$table_prefix = 'wp_';

require_once(ABSPATH . 'wp-settings.php');
