<?php

namespace Dxw\Whippet\Services;

// A thin wrapper around the low-level API functions
class BaseApi
{
    private $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'headers' => [ 'User-Agent' => 'Whippet https://github.com/dxw/whippet/' ]
        ]);
    }

    public function get($url)
    {
        try {
            $response = $this->client->get($url);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return \Result\Result::err('Failed to connect to '.$url);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return \Result\Result::err('Failed to receive data from '.$url);
        }

        return \Result\Result::ok($response->getBody());
    }
}
