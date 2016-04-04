<?php

trait Helpers
{
    private function getWhippetLock(/* string */ $hash, array $dependencyMap)
    {
        $whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\Files\\WhippetLock')
        ->disableOriginalConstructor()
        ->getMock();

        $whippetLock->method('getHash')
        ->willReturn($hash);

        $map = [];
        foreach ($dependencyMap as $dependencyType => $return) {
            $map[] = [$dependencyType, $return];
        }

        $whippetLock->method('getDependencies')
        ->will($this->returnValueMap($map));

        return $whippetLock;
    }

    private function getFileLocator($return)
    {
        $fileLocator = $this->getMockBuilder('\\Dxw\\Whippet\\FileLocator')
        ->disableOriginalConstructor()
        ->getMock();

        $fileLocator->method('getDirectory')
        ->willReturn($return);

        return $fileLocator;
    }

    private function getGit($isRepo, $cloneRepo, $checkout)
    {
        $git = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Git')
        ->disableOriginalConstructor()
        ->getMock();

        $git->method('is_repo')
        ->willReturn($isRepo);

        if ($cloneRepo !== null) {
            $git->expects($this->exactly(1))
            ->method('clone_repo')
            ->with($cloneRepo)
            ->will($this->returnCallback(function () { echo "git clone output\n"; }));
        }

        $git->expects($this->exactly(1))
        ->method('checkout')
        ->with($checkout)
        ->will($this->returnCallback(function () { echo "git checkout output\n"; }));

        return $git;
    }

    private function getFactory(array $newInstanceMap, array $callStaticMap)
    {
        $factory = $this->getMockBuilder('\\Dxw\\Whippet\\Factory')
        ->disableOriginalConstructor()
        ->getMock();

        $factory->method('newInstance')
        ->will($this->returnValueMap($newInstanceMap));

        $factory->method('callStatic')
        ->will($this->returnValueMap($callStaticMap));

        return $factory;
    }
}
