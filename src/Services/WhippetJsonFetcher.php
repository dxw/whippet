<?php

namespace Dxw\Whippet\Services;

class ManifestFetcher
{
    public function __construct($fileFetcher, $JSONDecoder)
    {
        $this->fileFetcher = $fileFetcher;
        $this->JSONDecoder = $JSONDecoder;
        $this->path = '/whippet.json';
    }

    public function fetch()
    {
        $result = $this->file_fetcher->fetch($this->path);
        if ($result->isErr()) {
            return($result);
        }
        $rawContent = $result->unwrap;

        $result = $this->JSONDecoder->decode($rawContent);
        if ($result->isErr()) {
            return($result);
        }

        $whippetJson = new \Dxw\Whippet\Files\WhippetJson($result->unwrap());
        return \Result\Result::ok($whippetJson);
    }
}
