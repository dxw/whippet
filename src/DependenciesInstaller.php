<?php

namespace Dxw\Whippet;

class DependenciesInstaller
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\FileLocator $fileLocator
    ) {
        $this->factory = $factory;
        $this->fileLocator = $fileLocator;
    }

    public function install()
    {
        $result = $this->fileLocator->getDirectory();
        $dir = $result->unwrap();

        if (!file_exists($dir.'/whippet.json')) {
            return \Result\Result::err('whippet.json not found');
        }

        $lockFile = $this->factory->callStatic('\\Dxw\\Whippet\\WhippetLock', 'fromFile', $dir.'/whippet.lock');

        $hash = sha1(file_get_contents($dir.'/whippet.json'));
        if ($lockFile->getHash() !== $hash) {
            return \Result\Result::err('mismatched hash - run `whippet dependencies update` first');
        }

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

        return \Result\Result::ok();
    }
}
