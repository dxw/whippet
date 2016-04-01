<?php

namespace Dxw\Whippet\Modules;

class Dependencies extends \RubbishThorClone
{
    public function __construct()
    {
        parent::__construct();

        $this->factory = new \Dxw\Whippet\Factory();
        $this->fileLocator = new \Dxw\Whippet\FileLocator(getcwd());
    }

    public function commands()
    {
        $this->command('install', 'Installs dependencies');
        $this->command('update', 'Updates dependencies to their latest versions');
    }

    private function exitIfError(\Result\Result $result)
    {
        if ($result->isErr()) {
            echo sprintf("ERROR: %s\n", $result->getErr());
            exit(1);
        }
    }

    public function install()
    {
        $installer = new \Dxw\Whippet\DependenciesInstaller($this->factory, $this->fileLocator);

        $this->exitIfError($installer->install());
    }

    public function update()
    {
        $updater = new \Dxw\Whippet\DependenciesUpdater($this->factory, $this->fileLocator);
        $installer = new \Dxw\Whippet\DependenciesInstaller($this->factory, $this->fileLocator);

        $this->exitIfError($updater->update());
        $this->exitIfError($installer->install());
    }
}
