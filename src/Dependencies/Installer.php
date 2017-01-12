<?php

namespace Dxw\Whippet\Dependencies;

class Installer
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\ProjectDirectory $dir
    ) {
        $this->factory = $factory;
        $this->dir = $dir;
    }

    public function install($dep = null)
    {
        $result = $this->loadWhippetFiles();
        if ($result->isErr()) {
            return $result;
        }
        if (is_null($dep)) {
            return $this->installAll();
        }
        return $this->installSingle($dep);
    }

    private function installAll()
    {
        $count = 0;

        foreach (['themes', 'plugins'] as $type) {
            foreach ($this->lockFile->getDependencies($type) as $dep) {
                $result = $this->installDependency($type, $dep);
                if ($result->isErr()) {
                    return $result;
                }

                ++$count;
            }
        }

        if ($count === 0) {
            echo "whippet.lock contains nothing to install\n";
        }

        return \Result\Result::ok();
    }

    private function installSingle($dep)
    {
        //Will only get here if $dep is valid format and matches an entry in whippet.json
        $type = explode('/', $dep)[0];
        $name = explode('/', $dep)[1];

        foreach ($this->lockFile->getDependencies($type) as $dep) {
            if ($dep['name'] === $name) {
                $result = $this->installDependency($type, $dep);
                if ($result->isErr()) {
                    return $result;
                }
                return \Result\Result::ok();
            }
        }
    }

    private function loadWhippetFiles()
    {
        if (!is_file($this->dir.'/whippet.json')) {
            return \Result\Result::err('whippet.json not found');
        }

        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
        if ($result->isErr()) {
            return \Result\Result::err(sprintf('whippet.lock: %s', $result->getErr()));
        }
        $this->lockFile = $result->unwrap();

        $hash = sha1(file_get_contents($this->dir.'/whippet.json'));
        if ($this->lockFile->getHash() !== $hash) {
            return \Result\Result::err('mismatched hash - run `whippet dependencies update` first');
        }

        return \Result\Result::ok();
    }

    private function installDependency($type, $dep)
    {
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

        return \Result\Result::ok();
    }
}
