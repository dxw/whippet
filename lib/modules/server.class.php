<?php

class Server extends RubbishThorClone {
  use manifest_io;
  use whippet_helpers {
    whippet_init as _whippet_init;
  }

  public function commands() {
    $this->command('start', 'Run wordpress in docker containers');
    $this->command('stop', 'Stop all containers');
    $this->command('db [connect|dump]', 'Connect to or dump data from MySQL');
    $this->command('ps', 'List status of containers');
    $this->command('logs [wordpress|mysql|mailcatcher]', 'Show logs for container');
  }

  private function whippet_init() {
    $this->_whippet_init();
    $this->mysql_data = 'whippet_mysql_data_'.preg_replace('/[^a-zA-Z0-9]/', '_', $this->project_dir);
  }

  private function _stop() {
    $this->whippet_init();

    system('docker stop whippet_mailcatcher whippet_mysql whippet_wordpress');
    system('docker rm whippet_mailcatcher whippet_mysql whippet_wordpress');
  }

  /*
   * Commands
   */

  /*
   * TODO: document
   */
  public function start($argv=null) {
    // workaround for RubbishThorClone bug
    if ($argv !== null) {
      parent::start($argv);
      return;
    }

    $this->whippet_init();

    system('docker run --name='.escapeshellarg($this->mysql_data).' -v /var/lib/mysql mysql /bin/true');

    # Stop/delete existing containers
    $this->_stop();

    # Start other containers
    system('docker run -d --name=whippet_mailcatcher -p 1080:1080 schickling/mailcatcher');
    system('docker run -d --name=whippet_mysql --volumes-from='.escapeshellarg($this->mysql_data).' -e MYSQL_DATABASE=wordpress -e MYSQL_ROOT_PASSWORD=foobar mysql');
    system('docker run -d --name=whippet_wordpress -v '.escapeshellarg($this->project_dir).':/usr/src/app -v '.escapeshellarg($this->project_dir).'/wp-content:/var/www/html/wp-content -p 8000:80 --link=whippet_mysql:mysql --link=whippet_mailcatcher:mailcatcher thedxw/whippet-wordpress');
  }

  /*
   * TODO: document
   */
  public function stop() {
    $this->whippet_init();

    $this->_stop();
  }

  /*
   * TODO: document
   */
  public function db($command) {
    $this->whippet_init();

    if ($command === 'connect') {
      passthru('docker run -ti --rm --link=whippet_mysql:db mysql sh -c \'exec mysql -h"$DB_PORT_3306_TCP_ADDR" -P"$DB_PORT_3306_TCP_PORT" -uroot -p"$DB_ENV_MYSQL_ROOT_PASSWORD" "$DB_ENV_MYSQL_DATABASE"\'');
    } else if ($command === 'dump') {
      passthru('docker run -ti --rm --link=whippet_mysql:db mysql sh -c \'exec mysqldump -h"$DB_PORT_3306_TCP_ADDR" -P"$DB_PORT_3306_TCP_PORT" -uroot -p"$DB_ENV_MYSQL_ROOT_PASSWORD" "$DB_ENV_MYSQL_DATABASE"\'');
    }
  }

  /*
   * TODO: document
   */
  public function ps() {
    $this->whippet_init();

    $regexp = '(^CONTAINER ID|whippet_wordpress\s*$|whippet_mysql\s*$|whippet_mailcatcher\s*$|'.$this->mysql_data.'\s*$)';
    passthru('docker ps -a | grep -E '.escapeshellarg($regexp));
  }

  /*
   * TODO: document
   */
  public function logs($container) {
    $this->whippet_init();

    passthru('docker logs -f whippet_'.escapeshellarg($container));
  }
};