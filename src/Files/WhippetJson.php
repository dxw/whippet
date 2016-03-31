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

    public function getSources()
    {
        return $this->data['src'];
    }
}
