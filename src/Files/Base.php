<?php

namespace Dxw\Whippet\Files;

abstract class Base
{
    public static function fromString(/* string */ $json)
    {
        return new static(json_decode($json, true));
    }

    public static function fromFile(/* string */ $path)
    {
        return self::fromString(file_get_contents($path));
    }

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function saveToPath(/* string */ $path)
    {
        file_put_contents($path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }
}
