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
        if (!is_file($this->dir.'/whippet.json')) {
            return \Result\Result::err('whippet.json not found');
        }

        if (!is_file($this->dir.'/whippet.lock')) {
            return \Result\Result::err('whippet.lock not found');
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
                    $result = $git->clone_repo($dep['src']);

                    if ($result === false) {
                        return \Result\Result::err('could not clone repository');
                    }
                } else {
                    echo sprintf("[Checking %s/%s]\n", $type, $dep['name']);
                }

                $result = $git->checkout($dep['revision']);
                if ($result === false) {
                    return \Result\Result::err('could not checkout revision');
                }
            }
        }

        return \Result\Result::ok();
    }
}
