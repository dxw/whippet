<?php

namespace Dxw\Whippet;

class ProjectDirectory
{
    public static function find(/* string */ $cwd)
    {
        $env = getenv('WHIPPET_PROJECT_DIRECTORY');
        if ($env) {
            return \Result\Result::ok(new self($env));
        }

        $path = $cwd;
        while (dirname($path) !== $path) {
            if (is_file($path.'/whippet.json')) {
                return \Result\Result::ok(new self($path));
            }

            $path = dirname($path);
        }

        return \Result\Result::err('whippet.json not found');
    }

    public function __construct(/* string */ $path)
    {
        $this->path = $path;
    }

    public function __toString()
    {
        return $this->path;
    }
}
