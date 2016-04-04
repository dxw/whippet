<?php

namespace Dxw\Whippet\Dependencies;

class Installer
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        /* string */ $dir
    ) {
        $this->factory = $factory;
        $this->dir = $dir;
    }

    public function install()
    {
        if (!file_exists($this->dir.'/whippet.json')) {
            return \Result\Result::err('whippet.json not found');
        }

        $lockFile = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');

        $hash = sha1(file_get_contents($this->dir.'/whippet.json'));
        if ($lockFile->getHash() !== $hash) {
            return \Result\Result::err('mismatched hash - run `whippet dependencies update` first');
        }

        foreach (['themes', 'plugins'] as $type) {
            foreach ($lockFile->getDependencies($type) as $dep) {
                $path = $this->dir.'/wp-content/'.$type.'/'.$dep['name'];

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
