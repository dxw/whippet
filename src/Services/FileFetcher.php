<?php

namespace Dxw\Whippet\Services;

// Thin wrapper around file access functions.
// Responsible for fetching and parsing json files
class FileFetcher
{
    public function __construct($dir)
    {
        $this->directory = $dir;
    }

    public function fetch($path)
    {
        $full_path = $this->directory.$path;
        if (!is_file($full_path)) {
            return \Result\Result::err('file not found '.$full_path);
        }

        return \Result\Result::ok(file_get_contents($full_path));
    }
}
