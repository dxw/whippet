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
        $fileLocator = new \Dxw\Whippet\Modules\Helpers\FileLocator(getcwd());
        $dependencies = new \Dxw\Whippet\Modules\Helpers\Dependencies($factory, $fileLocator);

        $dependencies->install();
    }
}
