<?php

namespace Dxw\Whippet\Services;

// A thin wrapper around the low-level API functions
class BaseApi
{
    public function get($url)
    {
        $response = file_get_contents($url);

        if ($response === false) {
            return \Result\Result::err('Failed to receive data from '.$url);
        }

        return \Result\Result::ok($response);
    }
}
