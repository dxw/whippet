<?php

namespace Dxw\Whippet\Modules;

class Server extends \RubbishThorClone
{
    use Helpers\ManifestIo;
    use Helpers\WhippetHelpers {
        whippet_init as _whippet_init;
    }

    public function commands()
    {
        $this->command('start', 'Run wordpress in docker containers');
        $this->command('stop', 'Stop all containers');
        $this->command('run', 'Alias for whippet server start && whippet server logs wordpress');
        $this->command('db [connect|dump|undump]', 'Connect to or dump data from MySQL');
        $this->command('ps', 'List status of containers');
        $this->command('logs [wordpress|mysql|mailcatcher]', 'Show logs for container');
        $this->command('console', 'Start a shell inside the WordPress container');
    }

    private function whippet_init()
    {
        $this->_whippet_init();
        $this->project_name = 'whippet_'.preg_replace('/[^a-zA-Z0-9]/', '_', $this->project_dir);
        $this->yml_path = $this->project_dir.'/docker-compose.yml';
        $this->args = '--project-name '.$this->project_name.' --file '.$this->yml_path;
    }

    private function _stop()
    {
        $this->whippet_init();

        exec('docker-compose '.$this->args.' down');
    }

    /*
    * Commands
    */

    /*
    * TODO: document
    */
    public function run()
    {
        $this->start();
        $this->logs('wordpress');
    }

    /*
    * TODO: document
    */
    public function start($argv = null)
    {
        // workaround for RubbishThorClone bug
        if ($argv !== null) {
            parent::start($argv);

            return;
        }

        $this->whippet_init();

        // Create docker-compose.yml
        $ymlFile = file_get_contents(__DIR__.'/../../docker-compose-files/docker-compose.yml');
        file_put_contents($this->yml_path, $ymlFile);

        // Stop/delete existing containers
        echo "Stopping already-running containers\n";
        $this->_stop();

        $output = null;
        $return = null;

        // Start containers
        exec('docker-compose '.$this->args.' up -d');

        echo "Started whippet containers\n";
    }

    /*
    * TODO: document
    */
    public function stop()
    {
        $this->whippet_init();

        $this->_stop();

        echo "Stopped and removed all whippet non-data containers\n";
    }

    /*
    * TODO: document
    */
    public function db($command = null)
    {
        $this->whippet_init();

        if ($command === 'connect' || $command === null) {
            die('TODO');
            passthru('docker run --label=com.dxw.whippet=true --label=com.dxw.data=false -ti --rm --link=whippet_mysql:mysql mysql sh -c \'MYSQL_PWD="$MYSQL_ENV_MYSQL_ROOT_PASSWORD" exec mysql -h"$MYSQL_PORT_3306_TCP_ADDR" -P"$MYSQL_PORT_3306_TCP_PORT" -uroot "$MYSQL_ENV_MYSQL_DATABASE"\'');
        } elseif ($command === 'dump') {
            die('TODO');
            passthru('docker run --label=com.dxw.whippet=true --label=com.dxw.data=false -ti --rm --link=whippet_mysql:mysql mysql sh -c \'MYSQL_PWD="$MYSQL_ENV_MYSQL_ROOT_PASSWORD" exec mysqldump -h"$MYSQL_PORT_3306_TCP_ADDR" -P"$MYSQL_PORT_3306_TCP_PORT" -uroot "$MYSQL_ENV_MYSQL_DATABASE"\'');
        } elseif ($command === 'undump') {
            die('TODO');
            passthru('docker run --label=com.dxw.whippet=true --label=com.dxw.data=false -i --rm --link=whippet_mysql:mysql mysql sh -c \'MYSQL_PWD="$MYSQL_ENV_MYSQL_ROOT_PASSWORD" exec mysql -h"$MYSQL_PORT_3306_TCP_ADDR" -P"$MYSQL_PORT_3306_TCP_PORT" -uroot "$MYSQL_ENV_MYSQL_DATABASE"\'');
        }
    }

    /*
    * TODO: document
    */
    public function ps()
    {
        $this->whippet_init();

        $regexp = '(^CONTAINER ID|whippet_wordpress\s*$|whippet_mysql\s*$|whippet_mailcatcher\s*$|'.$this->mysql_data.'\s*$)';
        die('TODO');
        passthru('docker ps -a | grep -E '.escapeshellarg($regexp));
    }

    /*
    * TODO: document
    */
    public function logs($container)
    {
        $this->whippet_init();

        passthru('docker-compose '.$this->args.' logs -f '.$container);
    }

    /*
    * TODO: document
    */
    public function console()
    {
        $this->whippet_init();

        die('TODO');
        passthru('docker exec -ti whippet_wordpress bash');
    }
};
