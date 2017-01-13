<?php

class Dependencies_Installer_Test extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    public function testInstallAll()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);
        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);
        $gitAnotherPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/another-plugin', '789abc');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/another-plugin', $gitAnotherPlugin);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n[Adding plugins/my-plugin]\ngit clone output\ngit checkout output\n[Adding plugins/another-plugin]\ngit clone output\ngit checkout output\n", $output);
    }

    public function testInstallAllThemeAlreadyCloned()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $git = $this->getGit(true, null, '27ba906');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Checking themes/my-theme]\ngit checkout output\n", $output);
    }

    public function testInstallAllMissingWhippetJson()
    {
        $dir = $this->getDir();

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.json not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallAllMissingWhippetLock()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found'));

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.lock: file not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallAllWrongHash()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock('123123', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('mismatched hash - run `whippet dependencies update` first', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallAllCloneFails()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, ['with' => 'git@git.dxw.net:wordpress-themes/my-theme', 'return' => false], null);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not clone repository', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\n", $output);
    }

    public function testInstallAllCheckoutFails()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', ['with' => '27ba906', 'return' => false]);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not checkout revision', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n", $output);
    }

    public function testInstallAllBlankLockfile()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("whippet.lock contains nothing to install\n", $output);
    }

    public function testInstallSingle()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyPlugin = $this->getGit(true, null, '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );
        ob_start();
        $result = $dependencies->installSingle('plugins/my-plugin');
        $output = ob_get_clean();

        $this->assertEquals("[Checking plugins/my-plugin]\ngit checkout output\n", $output);
        $this->assertFalse($result->isErr());
    }

    public function testInstallSingleAlreadyCloned()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );
        ob_start();
        $result = $dependencies->installSingle('plugins/my-plugin');
        $output = ob_get_clean();

        $this->assertEquals("[Adding plugins/my-plugin]\ngit clone output\ngit checkout output\n", $output);
        $this->assertFalse($result->isErr());
    }

    public function testInstallSingleCloneFails()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, ['with' => 'git@git.dxw.net:wordpress-themes/my-theme', 'return' => false], null);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not clone repository', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\n", $output);
    }

    public function testInstallSingleCheckoutFails()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', ['with' => '27ba906', 'return' => false]);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->installSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not checkout revision', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n", $output);
    }
}
