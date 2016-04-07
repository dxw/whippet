<?php

namespace Dxw\Whippet\Modules;

class Deploy
{
    use Common;

    public function __construct()
    {
        $this->factory = new \Dxw\Whippet\Factory();
        $this->projectDirectory = \Dxw\Whippet\ProjectDirectory::find(getcwd());
    }

    public function deploy($force, $keep)
    {
        $dir = $this->getDirectory();
        $deployment = new \Dxw\Whippet\Deployment($this->factory, $dir);

        $this->exitIfError($deployment->deploy($force, $keep));
    }
}
