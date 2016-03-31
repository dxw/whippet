<?php

namespace Dxw\Whippet\Modules\Helpers;

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

        return \Result\Result::err('plugins file not found');
    }
}
