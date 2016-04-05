<?php

class Dependencies_Installer_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

    public function testInstall()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

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

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906');
        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $gitAnotherPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/another-plugin', '789abc');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme],
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin],
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/another-plugin', $gitAnotherPlugin],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

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
        file_put_contents($dir.'/whippet.lock', 'foobar');

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

        $git = $this->getGit(true, null, '27ba906');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

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

        $factory = $this->getNullFactory();

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.json not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallMissingWhippetLock()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');

        $factory = $this->getFactory([], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.lock: file not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallWrongHash()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock('123123', []);

        $factory = $this->getFactory([], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('mismatched hash - run `whippet dependencies update` first', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallCloneFails()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

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

        $gitMyTheme = $this->getGit(false, ['with' => 'git@git.dxw.net:wordpress-themes/my-theme', 'return' => false], null);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not clone repository', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\n", $output);
    }

    public function testInstallCheckoutFails()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

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

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', ['with' => '27ba906', 'return' => false]);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not checkout revision', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n", $output);
    }

    public function testInstallBlankLockfile()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [],
            'plugins' => [],
        ]);

        $factory = $this->getFactory([
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer($factory, $dir);

        ob_start();
        $result = $dependencies->install();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("whippet.lock contains nothing to install\n", $output);
    }
}
