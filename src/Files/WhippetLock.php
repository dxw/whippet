<?php

namespace Dxw\Whippet\Files;

class WhippetLock extends Base
{
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
}
