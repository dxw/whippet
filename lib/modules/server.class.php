<?php

class Server extends RubbishThorClone {
  use manifest_io;
  use whippet_helpers;

  public function commands() {
    $this->command('start', 'Run wordpress in docker containers');
    $this->command('db [connect|dump]', 'Connect to or dump data from MySQL');
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

    $mysql_data = 'whippet_mysql_data_'.preg_replace('/[^a-zA-Z0-9]/', '_', $this->project_dir);
    system('docker run --name='.escapeshellarg($mysql_data).' -v /var/lib/mysql mysql /bin/true');

    # Stop/delete existing containers
    system('docker stop whippet_mailcatcher whippet_mysql whippet_wordpress');
    system('docker rm whippet_mailcatcher whippet_mysql whippet_wordpress');

    # Start other containers
    system('docker run -d --name=whippet_mailcatcher -p 1080:1080 schickling/mailcatcher');
    system('docker run -d --name=whippet_mysql --volumes-from='.$mysql_data.' -e MYSQL_DATABASE=wordpress -e MYSQL_ROOT_PASSWORD=foobar mysql');
    system('docker run -d --name=whippet_wordpress -v '.escapeshellarg($this->project_dir).':/usr/src/app -p 8000:8000 --link=whippet_mysql:db --link=whippet_mailcatcher:mailcatcher thedxw/whippet-server-custom');
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
};
