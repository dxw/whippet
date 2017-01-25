<?php

namespace Dxw\Whippet\Models;

// Responsible for wrapping data about dxw inspections in an object
class Inspection
{
    public $date;
    public $versions;
    public $result;
    public $url;

    public function __construct($date_string, $versions, $result, $url)
    {
        $this->date = date_create($date_string);
        $this->versions = $versions;
        $this->result = $result;
        $this->url = $url;
    }
}
