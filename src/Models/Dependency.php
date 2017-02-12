<?php

namespace Dxw\Whippet\Models;

class Dependency
{
    private $dep;
    private $type;
    private $default_repo;

    public function __construct(array $dep, string $type, $default_repo)
    {
        $this->dep = $dep;
        $this->type = $type;
        $this->default_repo = $default_repo;
    }

    public function name()
    {
        return $this->dep['name'];
    }

    public function type()
    {
        return $this->type;
    }

    public function ref()
    {
        if (isset($this->dep['ref'])) {
            return $this->dep['ref'];
        }
        return 'master';
    }

    public function src()
    {
        if (isset($this->dep['src'])) {
            return $this->dep['src'];
        } else {
            return $this->defaultSrc();
        }
    }

    private function defaultSrc()
    {
        if (isset($this->default_repo)) {
            return $this->default_repo.$this->name();
        }
    }
}
