<?php

class DependenciesInstaller_Test extends PHPUnit_Framework_TestCase
{
    private function getWhippetLock(/* string */ $hash, array $dependencyMap)
    {
        $whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\WhippetLock')
        ->disableOriginalConstructor()
        ->getMock();

        $whippetLock->method('getHash')
        ->willReturn($hash);

        foreach ($dependencyMap as $dependencyType => $return) {
            $whippetLock->method('getDependencies')
            ->with($dependencyType)
            ->willReturn($return);
        }

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

    public function testInstall()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ]);

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $git = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ], [
            ['\\Dxw\\Whippet\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesInstaller($factory, $fileLocator);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n", $output);
    }

    public function testInstallThemeAlreadyCloned()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');

        mkdir($dir.'/wp-content/themes/my-theme');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ]);

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $git = $this->getGit(true, null, '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ], [
            ['\\Dxw\\Whippet\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesInstaller($factory, $fileLocator);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Checking themes/my-theme]\ngit checkout output\n", $output);
    }

    public function testInstallMissingWhippetJson()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $factory = $this->getFactory([], []);

        $dependencies = new \Dxw\Whippet\DependenciesInstaller($factory, $fileLocator);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.json not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallWrongHash()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $whippetLock = $this->getWhippetLock('123123', []);

        $factory = $this->getFactory([], [
            ['\\Dxw\\Whippet\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesInstaller($factory, $fileLocator);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('mismatched hash - run `whippet dependencies update` first', $result->getErr());
        $this->assertEquals('', $output);
    }
}
