<?php

namespace Dxw\Whippet;

class FileLocator
{
    public function __construct(/* string */ $cwd)
    {
        $this->cwd = $cwd;
    }

    public function getDirectory()
    {
        $path = $this->cwd;
        while (dirname($path) !== $path) {
            if (file_exists($path.'/plugins') || file_exists($path.'/whippet.json')) {
                return \Result\Result::ok($path);
            }

            $path = dirname($path);
        }

        return \Result\Result::err('whippet.json not found, plugins file not found');
    }
}
