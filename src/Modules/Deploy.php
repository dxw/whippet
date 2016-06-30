<?php

namespace Dxw\Whippet\Modules;

class Deploy
{
    use Common;

    public function __construct(/* string */ $deployDir)
    {
        $this->factory = new \Dxw\Whippet\Factory();
        $this->projectDirectory = \Dxw\Whippet\ProjectDirectory::find(getcwd());
        $this->deployDir = $deployDir;
    }

    public function deploy($force, $keep)
    {
        $dir = $this->getDirectory();
        $deployment = new \Dxw\Whippet\Deployment($this->factory, $dir, $this->deployDir);

        $this->exitIfError($deployment->deploy($force, $keep));
    }
}
