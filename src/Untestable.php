<?php

namespace Dxw\Whippet;

class Untestable
{
    public function die()
    {
        die();
    }

    public function exit($code)
    {
        exit($code);
    }
}
