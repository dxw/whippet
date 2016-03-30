<?php

namespace Dxw\Whippet;

class Untestable
{
    public function _die()
    {
        die();
    }

    public function _exit($code)
    {
        exit($code);
    }
}
