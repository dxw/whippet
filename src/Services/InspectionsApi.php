<?php

namespace Dxw\Whippet\Services;

// Responsible for calling an API and returning information about dxw inspections
// associated with a plugin
class InspectionsApi
{
    public function __construct(
        $host,
        \Dxw\Whippet\Services\JsonApi $json_api
    ) {
        $this->host = $host;
        $this->jsonApi = $json_api;
    }

    public function get_inspections($plugin_slug)
    {
        $result = $this->jsonApi->get($this->url($plugin_slug));
        if ($result->isErr()) {
            return $result;
        }
        $inspections = $this->buildInspections($result->unwrap());
        return \Result\Result::ok($inspections);
    }

    private function buildInspections($raw_inspections)
    {
        return array_map(function ($raw_inspection) {
            return new Inspection(
                $raw_inspection['date'],
                $raw_inspection['result'],
                $raw_inspection['url']
            );
        }, $raw_inspections);
    }

    private function url($plugin_slug)
    {
        return $this->host.'/wp-json/v1/inspections/'.$plugin_slug;
    }
}

class Inspection
{
    public $date;
    public $result;
    public $url;

    public function __construct($date_string, $result, $url)
    {
        $this->date = date_create($date_string);
        $this->result = $result;
        $this->url = $url;
    }
}
