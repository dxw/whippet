<?php

namespace Dxw\Whippet\Files;

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
        if (!isset($this->data[$type])) {
            return [];
        }

        return $this->data[$type];
    }

    public function getHash()
    {
        return $this->data['hash'];
    }

    public function setHash(/* string */ $hash)
    {
        $this->data['hash'] = $hash;
    }

    public function addDependency(/* string */ $type, /* string */ $name, /* string */ $src, /* string */ $revision)
    {
        $this->data[$type][] = [
            'name' => $name,
            'src' => $src,
            'revision' => $revision,
        ];
    }

    public function saveToPath(/* string */ $path)
    {
        file_put_contents($path, json_encode($this->data));
    }
}
