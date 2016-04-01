<?php

namespace Dxw\Whippet\Modules;

class Dependencies extends \RubbishThorClone
{
    public function commands()
    {
        $this->command('install', 'Installs dependencies (themes)');
    }

    public function install()
    {
        $factory = new \Dxw\Whippet\Factory();
        $fileLocator = new \Dxw\Whippet\FileLocator(getcwd());
        $dependencies = new \Dxw\Whippet\DependenciesInstaller($factory, $fileLocator);

        $result = $dependencies->install();

        if ($result->isErr()) {
            echo sprintf("ERROR: %s\n", $result->getErr());
            exit(1);
        }
    }
}
