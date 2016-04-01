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
            ->with($cloneRepo);
        }

        $git->expects($this->exactly(1))
        ->method('checkout')
        ->with($checkout);

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

        $git = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ], [
            ['\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
        ]);

        $dependencies = new \Dxw\Whippet\Modules\Helpers\Dependencies($factory, $fileLocator);

        $dependencies->install();
    }

    public function testInstallThemeAlreadyCloned()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        mkdir($dir.'/wp-content/themes/my-theme');

        $whippetLock = $this->getWhippetLock('themes', [
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ]);

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $git = $this->getGit(true, null, '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ], [
            ['\\Dxw\\Whippet\\Modules\\Helpers\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
        ]);

        $dependencies = new \Dxw\Whippet\Modules\Helpers\Dependencies($factory, $fileLocator);

        $dependencies->install();
    }
}
