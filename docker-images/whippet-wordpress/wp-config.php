<?php

// Per-project configuration
if (file_exists('/usr/src/app/config/server.php')) {
  require('/usr/src/app/config/server.php');
}

// Per-host configuration (for setting WP_SITEURL to http://x.local etc)
if (file_exists('/usr/src/app/config/server-local.php')) {
  require('/usr/src/app/config/server-local.php');
}

// Password nerfing
if (!defined('DISABLE_PASSWORD_NERFING')) {
  function wp_check_password(){return true;}
}

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
