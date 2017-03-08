<?php

namespace Dxw\Whippet\Files;

class WhippetLock
{
    public static function fromString(/* string */ $json)
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return \Result\Result::err('invalid JSON');
        }

        return \Result\Result::ok(new static($data));
    }

    public static function fromFile(/* string */ $path)
    {
        if (!is_file($path)) {
            return \Result\Result::err('file not found');
        }

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
        if (isset($this->data[$type])) {
            foreach ($this->data[$type] as $key => $dependency) {
                if ($name === $dependency['name']) {
                    array_splice($this->data[$type], $key, 1);
                }
            }
        }

        $this->data[$type][] = [
            'name' => $name,
            'src' => $src,
            'revision' => $revision,
        ];
    }
}
