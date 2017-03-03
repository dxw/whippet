<?php

namespace Dxw\Whippet\Services;

// Thin wrapper around json_decode, providing an easier to use interface
class JSONDecoder
{
    public function decode($json)
    {
        if (empty($json)) {
            return \Result\Result::err('Empty JSON');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return \Result\Result::err('Invalid JSON: '.json_last_error_msg());
        }
        return \Result\Result::ok($data);
    }
}
