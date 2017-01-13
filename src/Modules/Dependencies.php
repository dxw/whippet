<?php

namespace Dxw\Whippet\Modules;

class Dependencies extends \RubbishThorClone
{
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

    private function exitIfError(\Result\Result $result)
    {
        if ($result->isErr()) {
            echo sprintf("ERROR: %s\n", $result->getErr());
            exit(1);
        }
    }

    private function getDirectory()
    {
        $this->exitIfError($this->projectDirectory);

        return $this->projectDirectory->unwrap();
    }

    public function install()
    {
        $dir = $this->getDirectory();
        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir);

        $this->exitIfError($installer->install());
    }

    public function update($dep = null)
    {
        $dir = $this->getDirectory();
        $updater = new \Dxw\Whippet\Dependencies\Updater($this->factory, $dir);
        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir);

        if (is_null($dep)) {
            $this->exitIfError($updater->updateAll());
            $this->exitIfError($installer->install());
        } else {
            $this->exitIfError($updater->updateSingle($dep));
            $this->exitIfError($installer->install($dep));
        }
    }

    public function migrate()
    {
        $dir = new \Dxw\Whippet\ProjectDirectory(getcwd());
        $migration = new \Dxw\Whippet\Dependencies\Migration($this->factory, $dir);
        $this->exitIfError($migration->migrate());
    }
}
