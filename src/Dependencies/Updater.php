<?php

namespace Dxw\Whippet\Dependencies;

class Updater
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        /* string */ $dir
    ) {
        $this->factory = $factory;
        $this->dir = $dir;
    }

    public function update()
    {
        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $this->dir.'/whippet.json');
        if ($result->isErr()) {
            return \Result\Result::err(sprintf('whippet.json: %s', $result->getErr()));
        }
        $jsonFile = $result->unwrap();

        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
        if ($result->isErr()) {
            $lockFile = $this->factory->newInstance('\\Dxw\\Whippet\\Files\\WhippetLock', []);
        } else {
            $lockFile = $result->unwrap();
        }

        $jsonHash = sha1(file_get_contents($this->dir.'/whippet.json'));
        $gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', $this->dir);

        $lockFile->setHash($jsonHash);

        $ignores = [];
        if (is_file($this->dir.'/.gitignore')) {
            $ignores = $gitignore->get_ignores();
        }

        // Iterate through locked dependencies and remove from gitignore
        foreach (['themes', 'plugins'] as $type) {
            foreach ($lockFile->getDependencies($type) as $dep) {
                $line = '/wp-content/'.$type.'/'.$dep['name']."\n";
                $index = array_search($line, $ignores);
                if ($index !== false) {
                    unset($ignores[$index]);
                }
            }
        }

        $count = 0;

        foreach (['themes', 'plugins'] as $type) {
            foreach ($jsonFile->getDependencies($type) as $dep) {
                echo sprintf("[Updating %s/%s]\n", $type, $dep['name']);

                if (isset($dep['src'])) {
                    $src = $dep['src'];
                } else {
                    $src = $jsonFile->getSources()[$type].$dep['name'];
                }

                $ref = 'master';
                if (isset($dep['ref'])) {
                    $ref = $dep['ref'];
                }

                $commitResult = $this->factory->callStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', $src, $ref);

                if ($commitResult->isErr()) {
                    return \Result\Result::err(sprintf('git command failed: %s', $commitResult->getErr()));
                }

                $lockFile->addDependency($type, $dep['name'], $src, $commitResult->unwrap());

                $ignores[] = '/wp-content/'.$type.'/'.$dep['name']."\n";

                ++$count;
            }
        }

        $lockFile->saveToPath($this->dir.'/whippet.lock');
        $gitignore->save_ignores(array_unique($ignores));

        if ($count === 0) {
            echo "whippet.json contains no dependencies\n";
        }

        return \Result\Result::ok();
    }
}
