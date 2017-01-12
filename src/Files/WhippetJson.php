<?php

namespace Dxw\Whippet\Files;

class WhippetJson extends Base
{
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
