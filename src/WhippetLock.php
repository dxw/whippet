<?php

namespace Dxw\Whippet;

class WhippetLock
{
    public static function fromString(/* string */ $json)
    {
        return new self(json_decode($json, true));
    }

    public static function fromFile(/* string */ $path)
    {
        return self::fromString(file_get_contents($path));
    }

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getDependencies(/* string */ $type)
    {
        return $this->data[$type];
    }

    public function getHash()
    {
        return $this->data['hash'];
    }
}
