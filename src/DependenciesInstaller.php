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

        foreach (['themes', 'plugins'] as $type) {
            foreach ($lockFile->getDependencies($type) as $dep) {
                $path = $dir.'/wp-content/'.$type.'/'.$dep['name'];

                $git = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Git', $path);

                if (!$git->is_repo()) {
                    echo sprintf("[Adding %s/%s]\n", $type, $dep['name']);
                    $git->clone_repo($dep['src']);
                } else {
                    echo sprintf("[Checking %s/%s]\n", $type, $dep['name']);
                }

                $git->checkout($dep['revision']);
            }
        }

        return \Result\Result::ok();
    }
}
