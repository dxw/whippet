<?php

class Modules_Helpers_Dependencies_Test extends PHPUnit_Framework_TestCase
{
    private function getWhippetLock($dependencyType, $return)
    {
        $whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock')
        ->disableOriginalConstructor()
        ->getMock();

        $whippetLock->method('getDependencies')
        ->with($dependencyType)
        ->willReturn($return);

        return $whippetLock;
    }

    private function getFileLocator($return)
    {
        $fileLocator = $this->getMockBuilder('\\Dxw\\Whippet\\Modules\\Helpers\\FileLocator')
        ->disableOriginalConstructor()
        ->getMock();

        $fileLocator->method('getDirectory')
        ->willReturn($return);

        return $fileLocator;
    }

    private function getGit($cloneRepo, $checkout)
    {
        $git = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Git')
        ->disableOriginalConstructor()
        ->getMock();

        $git->expects($this->exactly(1))
        ->method('clone_repo')
        ->with($cloneRepo);

        $git->expects($this->exactly(1))
        ->method('checkout')
        ->with($checkout);

        return $git;
    }

    private function getFactory($valueMap)
    {
        $factory = $this->getMockBuilder('\\Dxw\\Whippet\\Factory')
        ->disableOriginalConstructor()
        ->getMock();

        $factory->method('newInstance')
        ->will($this->returnValueMap($valueMap));

        return $factory;
    }

    public function testInstall()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetLock = $this->getWhippetLock('themes', [
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ]);

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $git = $this->getGit('git@git.dxw.net:wordpress-themes/my-theme', '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock', $dir.'/whippet.lock', $whippetLock],
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ]);

        $dependencies = new \Dxw\Whippet\Modules\Helpers\Dependencies($factory, $fileLocator);

        $dependencies->install();
    }
}
