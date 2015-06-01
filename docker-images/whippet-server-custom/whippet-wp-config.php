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
define('DB_HOST', getenv('DB_PORT_3306_TCP_ADDR').':'.getenv('DB_PORT_3306_TCP_PORT'));
define('DB_NAME', getenv('DB_ENV_MYSQL_DATABASE'));
define('DB_USER', 'root');
define('DB_PASSWORD', getenv('DB_ENV_MYSQL_ROOT_PASSWORD'));
