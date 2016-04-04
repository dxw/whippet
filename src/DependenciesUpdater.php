<?php

namespace Dxw\Whippet;

class DependenciesUpdater
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\FileLocator $fileLocator
    ) {
        $this->factory = $factory;
        $this->fileLocator = $fileLocator;
    }

    public function update()
    {
        $result = $this->fileLocator->getDirectory();
        $dir = $result->unwrap();

        $jsonHash = sha1(file_get_contents($dir.'/whippet.json'));
        $jsonFile = json_decode(file_get_contents($dir.'/whippet.json'), true);
        $lockFile = $this->factory->newInstance('\\Dxw\\Whippet\\Files\\WhippetLock', []);
        $lockFile->setHash($jsonHash);
        $gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir);

        $ignores = $gitignore->get_ignores();

        foreach (['themes', 'plugins'] as $type) {
            $deps = isset($jsonFile[$type]) ? $jsonFile[$type] : [];
            foreach ($deps as $dep) {
                echo sprintf("[Updating %s/%s]\n", $type, $dep['name']);

                $src = $jsonFile['src'][$type].$dep['name'];
                $commitResult = $this->factory->callStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', $src, $dep['ref']);

                $lockFile->addDependency($type, $dep['name'], $src, $commitResult->unwrap());

                $ignores[] = '/wp-content/'.$type.'/'.$dep['name']."\n";
            }
        }

        $lockFile->saveToPath($dir.'/whippet.lock');
        $gitignore->save_ignores(array_unique($ignores));

        return \Result\Result::ok();
    }
}
