<?php

namespace Dxw\Whippet\Services;

// Responsible for:
// - calling out to a basic API
// - parsing JSON
// - converting error codes
class JsonApi
{
    public function __construct(
        \Dxw\Whippet\Services\BaseApi $base_api
    ) {
        $this->baseApi = $base_api;
    }

    public function get($url)
    {
        $response = $this->baseApi->get($url);

        if ($response->isErr()) {
            return $response;
        }

        $response_as_array = json_decode($response->unwrap(), true);
        if (is_null($response_as_array)) {
            return \Result\Result::err('Failed to parse response body as JSON when requesting '.$url);
        }
        return \Result\Result::ok($response_as_array);
    }
}
