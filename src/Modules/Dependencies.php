<?php

namespace Dxw\Whippet\Modules;

class Dependencies extends \RubbishThorClone
{
    use Common;

    public function __construct()
    {
        parent::__construct();

        $this->factory = new \Dxw\Whippet\Factory();
        $this->projectDirectory = \Dxw\Whippet\ProjectDirectory::find(getcwd());
    }

    public function commands()
    {
        $this->command('install', 'Installs dependencies');
        $this->command('update', 'Updates dependencies to their latest versions');
        $this->command('migrate', 'Converts legacy plugins file to whippet.json');
    }

    public function install()
    {
        $dir = $this->getDirectory();
        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir);

        $this->exitIfError($installer->install());
    }

    public function update()
    {
        $dir = $this->getDirectory();
        $updater = new \Dxw\Whippet\Dependencies\Updater($this->factory, $dir);
        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir);

        $this->exitIfError($updater->update());
        $this->exitIfError($installer->install());
    }

    public function migrate()
    {
        $dir = new \Dxw\Whippet\ProjectDirectory(getcwd());
        $migration = new \Dxw\Whippet\Dependencies\Migration($this->factory, $dir);
        $this->exitIfError($migration->migrate());
    }
}
