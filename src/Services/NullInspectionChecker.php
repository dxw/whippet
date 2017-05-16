<?php

namespace Dxw\Whippet\Services;

// Null object that does no checking
class NullInspectionChecker
{
    public function check($_type, $_dependency)
    {
        return \Result\Result::ok('');
    }
}
