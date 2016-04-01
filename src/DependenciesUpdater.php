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
        $lockFile = [
            'hash' => $jsonHash,
            'themes' => [],
        ];
        $gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir);

        $ignores = $gitignore->get_ignores();

        foreach ($jsonFile['themes'] as $theme) {
            echo sprintf("[Updating themes/%s]\n", $theme['name']);

            $src = $jsonFile['src']['themes'].$theme['name'];
            $commitResult = $this->factory->callStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', $src, $theme['ref']);

            $lockFile['themes'][] = [
                'name' => $theme['name'],
                'src' => $src,
                'revision' => $commitResult->unwrap(),
            ];

            $ignores[] = '/wp-content/themes/'.$theme['name']."\n";
        }

        file_put_contents($dir.'/whippet.lock', json_encode($lockFile));
        $gitignore->save_ignores(array_unique($ignores));

        return \Result\Result::ok();
    }
}
