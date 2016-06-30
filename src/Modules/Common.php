<?php

namespace Dxw\Whippet\Modules;

trait Common
{
    private function exitIfError(\Result\Result $result)
    {
        if ($result->isErr()) {
            echo sprintf("ERROR: %s\n", $result->getErr());
            exit(1);
        }
    }

    private function getDirectory()
    {
        $this->exitIfError($this->projectDirectory);

        return $this->projectDirectory->unwrap();
    }
}
