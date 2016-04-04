<?php

class DependenciesInstaller_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

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
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
                    'revision' => '123456',
                ],
                [
                    'name' => 'another-plugin',
                    'src' => 'git@git.dxw.net:wordpress-plugins/another-plugin',
                    'revision' => '789abc',
                ],
            ],
        ]);

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906');
        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $gitAnotherPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/another-plugin', '789abc');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme],
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin],
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/another-plugin', $gitAnotherPlugin],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesInstaller($factory, $fileLocator);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n[Adding plugins/my-plugin]\ngit clone output\ngit checkout output\n[Adding plugins/another-plugin]\ngit clone output\ngit checkout output\n", $output);
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
            'plugins' => [],
        ]);

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $git = $this->getGit(true, null, '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
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
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', $whippetLock],
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
