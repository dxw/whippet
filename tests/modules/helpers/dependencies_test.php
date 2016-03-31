<?php

class Modules_Helpers_Dependencies_Test extends PHPUnit_Framework_TestCase
{
    public function testInstall()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock')
        ->disableOriginalConstructor()
        ->getMock();

        $whippetLock->method('getDependencies')
        ->with('themes')
        ->willReturn([
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ]);

        $git = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Git')
        ->disableOriginalConstructor()
        ->getMock();

        $git->expects($this->exactly(1))
        ->method('clone_repo')
        ->with('git@git.dxw.net:wordpress-themes/my-theme');

        $git->expects($this->exactly(1))
        ->method('checkout')
        ->with('27ba906');

        $factory = $this->getMockBuilder('\\Dxw\\Whippet\\Factory')
        ->disableOriginalConstructor()
        ->getMock();

        $factory->method('newInstance')
        ->will($this->returnValueMap([
            ['\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock', $dir.'/whippet.lock', $whippetLock],
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ]));

        $fileLocator = $this->getMockBuilder('\\Dxw\\Whippet\\Modules\\Helpers\\FileLocator')
        ->disableOriginalConstructor()
        ->getMock();

        $fileLocator->method('getDirectory')
        ->willReturn(\Result\Result::ok($dir));

        $dependencies = new \Dxw\Whippet\Modules\Helpers\Dependencies($factory, $fileLocator);

        $dependencies->install();
    }
}
