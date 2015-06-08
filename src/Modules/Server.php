<?php

namespace Dxw\Whippet\Modules;

class Server extends \RubbishThorClone {
  use Helpers\ManifestIo;
  use Helpers\WhippetHelpers {
    whippet_init as _whippet_init;
  }

  public function commands() {
    $this->command('start', 'Run wordpress in docker containers');
    $this->command('stop', 'Stop all containers');
    $this->command('run', 'Alias for whippet server start && whippet server logs wordpress');
    $this->command('db [connect|dump]', 'Connect to or dump data from MySQL');
    $this->command('ps', 'List status of containers');
    $this->command('logs [wordpress|mysql|mailcatcher]', 'Show logs for container');
    $this->command('console', 'Start a shell inside the WordPress container');
  }

  private function whippet_init() {
    $this->_whippet_init();
    $this->mysql_data = 'whippet_mysql_data_'.preg_replace('/[^a-zA-Z0-9]/', '_', $this->project_dir);
  }

  private function _stop() {
    $this->whippet_init();

    exec('docker stop whippet_mailcatcher whippet_mysql whippet_wordpress 2>/dev/null');
    exec('docker rm whippet_mailcatcher whippet_mysql whippet_wordpress 2>/dev/null');
  }

  /*
   * Commands
   */

  /*
   * TODO: document
   */
  public function run() {
    $this->start();
    $this->logs('wordpress');
  }

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

    # Ensure data container exists
    exec('docker run --label=com.dxw.whippet=true --label=com.dxw.data=true --name='.escapeshellarg($this->mysql_data).' -v /var/lib/mysql mysql /bin/true 2>/dev/null');

    # Stop/delete existing containers
    echo "Stopping already-running containers\n";
    $this->_stop();

    $output = null;
    $return = null;

    # Start other containers
    exec('docker run -d --label=com.dxw.whippet=true --label=com.dxw.data=false --name=whippet_mailcatcher -p 1080:1080 schickling/mailcatcher 2>/dev/null', $output, $return);
    if ($return !== 0) {
      echo "Mailcatcher container failed to start\n";
      exit(1);
    }
    exec('docker run -d --label=com.dxw.whippet=true --label=com.dxw.data=false --name=whippet_mysql --volumes-from='.escapeshellarg($this->mysql_data).' -e MYSQL_DATABASE=wordpress -e MYSQL_ROOT_PASSWORD=foobar mysql 2>/dev/null', $output, $return);
    if ($return !== 0) {
      echo "MySQL container failed to start\n";
      exit(1);
    }
    exec('docker run -d --label=com.dxw.whippet=true --label=com.dxw.data=false --name=whippet_wordpress -v '.escapeshellarg($this->project_dir).':/usr/src/app -v '.escapeshellarg($this->project_dir).'/wp-content:/var/www/html/wp-content -p 8000:8000 --link=whippet_mysql:mysql --link=whippet_mailcatcher:mailcatcher -e PROJECT_ID='.escapeshellarg(md5($this->project_dir)).' thedxw/whippet-server-custom 2>/dev/null', $output, $return);
    if ($return !== 0) {
      echo "WordPress container failed to start\n";
      exit(1);
    }

    echo "Started whippet containers\n";
  }

  /*
   * TODO: document
   */
  public function stop() {
    $this->whippet_init();

    $this->_stop();

    echo "Stopped and removed all whippet non-data containers\n";
  }

  /*
   * TODO: document
   */
  public function db($command=null) {
    $this->whippet_init();

    if ($command === 'connect' || $command === null) {
      passthru('docker run --label=com.dxw.whippet=true --label=com.dxw.data=false -ti --rm --link=whippet_mysql:mysql mysql sh -c \'exec mysql -h"$MYSQL_PORT_3306_TCP_ADDR" -P"$MYSQL_PORT_3306_TCP_PORT" -uroot -p"$MYSQL_ENV_MYSQL_ROOT_PASSWORD" "$MYSQL_ENV_MYSQL_DATABASE"\'');
    } else if ($command === 'dump') {
      passthru('docker run --label=com.dxw.whippet=true --label=com.dxw.data=false -ti --rm --link=whippet_mysql:mysql mysql sh -c \'exec mysqldump -h"$MYSQL_PORT_3306_TCP_ADDR" -P"$MYSQL_PORT_3306_TCP_PORT" -uroot -p"$MYSQL_ENV_MYSQL_ROOT_PASSWORD" "$MYSQL_ENV_MYSQL_DATABASE"\'');
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

  /*
   * TODO: document
   */
  public function console() {
    $this->whippet_init();

    passthru('docker exec -ti whippet_wordpress bash');
  }
};
