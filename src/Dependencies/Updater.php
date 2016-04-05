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
        $jsonHash = sha1(file_get_contents($this->dir.'/whippet.json'));
        $jsonFile = json_decode(file_get_contents($this->dir.'/whippet.json'), true);
        $lockFile = $this->factory->newInstance('\\Dxw\\Whippet\\Files\\WhippetLock', []);
        $lockFile->setHash($jsonHash);
        $gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', $this->dir);

        $ignores = $gitignore->get_ignores();

        $count = 0;

        foreach (['themes', 'plugins'] as $type) {
            $deps = isset($jsonFile[$type]) ? $jsonFile[$type] : [];
            foreach ($deps as $dep) {
                echo sprintf("[Updating %s/%s]\n", $type, $dep['name']);

                if (isset($dep['src'])) {
                    $src = $dep['src'];
                } else {
                    $src = $jsonFile['src'][$type].$dep['name'];
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
