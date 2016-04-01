<?php

namespace Dxw\Whippet\Modules\Helpers;

class DependenciesInstaller
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\Modules\Helpers\FileLocator $fileLocator
    ) {
        $this->factory = $factory;
        $this->fileLocator = $fileLocator;
    }

    public function install()
    {
        $result = $this->fileLocator->getDirectory();
        $dir = $result->unwrap();

        $lockFile = $this->factory->callStatic('\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock', 'fromFile', $dir.'/whippet.lock');

        foreach ($lockFile->getDependencies('themes') as $theme) {
            $path = $dir.'/wp-content/themes/'.$theme['name'];

            $git = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Git', $path);

            if (!$git->is_repo()) {
                echo sprintf("[Adding themes/%s]\n", $theme['name']);
                $git->clone_repo($theme['src']);
            } else {
                echo sprintf("[Checking themes/%s]\n", $theme['name']);
            }

            $git->checkout($theme['revision']);
        }
    }
}
