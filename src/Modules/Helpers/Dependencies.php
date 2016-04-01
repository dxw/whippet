<?php

namespace Dxw\Whippet\Modules\Helpers;

class Dependencies
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

        $lockFile = $this->factory->newInstance('\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock', $dir.'/whippet.lock');

        foreach ($lockFile->getDependencies('themes') as $theme) {
            $path = $dir.'/wp-content/themes/'.$theme['name'];

            $git = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Git', $path);

            if (!$git->is_repo()) {
                $git->clone_repo($theme['src']);
            }

            $git->checkout($theme['revision']);
        }
    }
}
