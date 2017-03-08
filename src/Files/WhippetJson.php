<?php

namespace Dxw\Whippet\Files;

class WhippetJson
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
        if (isset($this->data[$type])) {
            return $this->data[$type];
        } else {
            return [];
        }
    }

    public function getDependency(/* string */ $type, /* string */ $name)
    {
        if (isset($this->data[$type])) {
            foreach ($this->getDependencies($type) as $dep) {
                if ($dep['name'] === $name) {
                    return $dep;
                }
            }
        }
        return [];
    }

    public function getSources()
    {
        return $this->data['src'];
    }
}
