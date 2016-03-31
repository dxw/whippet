<?php

namespace Dxw\Whippet\Modules\Helpers;

class WhippetLock
{
    public function __construct(/* string */ $path)
    {
        $this->data = json_decode(file_get_contents($path), true);
    }

    public function getDependencies(/* string */ $type)
    {
        return $this->data[$type];
    }
}
