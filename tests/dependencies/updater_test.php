<?php

class Dependencies_Updater_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

    private function getGitignore(array $get, array $save, /* bool */ $saveIgnores, /* bool */ $warnOnGet)
    {
        $gitignore = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Gitignore')
        ->disableOriginalConstructor()
        ->getMock();

        $getIgnores = $gitignore->method('get_ignores');
        if ($warnOnGet) {
            $getIgnores->will($this->returnCallback(function () { trigger_error('$warOnGet set but not prevented', E_USER_WARNING); }));
        } else {
            $getIgnores->willReturn($get);
        }

        $gitignore->expects($this->exactly($saveIgnores ? 1 : 0))
        ->method('save_ignores')
        ->with($save);

        return $gitignore;
    }

    private function getWhippetLockWritable(array $addDependency, /* string */ $hash, /* string */ $path, array $getDependencies)
    {
        $whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\Files\\WhippetLock')
        ->disableOriginalConstructor()
        ->getMock();

        call_user_func_array(
            [
                $whippetLock->expects($this->exactly(count($addDependency)))
                ->method('addDependency'),
                'withConsecutive',
            ],
            $addDependency
        );

        $whippetLock->expects($this->exactly(1))
        ->method('setHash')
        ->with($hash);

        $whippetLock->expects($this->exactly($path === null ? 0 : 1))
        ->method('saveToPath')
        ->with($path);

        if ($getDependencies === []) {
            $getDependencies = [
                ['themes', []],
                ['plugins', []],
            ];
        }

        $whippetLock->method('getDependencies')
        ->will($this->returnValueMap($getDependencies));

        return $whippetLock;
    }

    public function testUpdate()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'ref' => 'v1.6',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
    }

    public function testUpdateWithExistingGitignore()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        touch($dir.'/.gitignore');

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
        ], [
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
            "/wp-content/themes/my-theme\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateWithExistingGitignoreNoDuplication()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        touch($dir.'/.gitignore');

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
            "/wp-content/themes/my-theme\n",
        ], [
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
            "/wp-content/themes/my-theme\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateFailedGitCommand()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], false, false);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::err('oh no')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('git command failed: oh no', $result->getErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateWithExplicitSrc()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                    'src' => 'foobar',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'foobar', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'foobar', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateWithoutRef()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'master', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateBlankJsonfile()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [], true, false);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("whippet.json contains no dependencies\n", $output);
    }

    public function testUpdateNoGitignore()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'ref' => 'v1.6',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, true);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
    }

    public function testUpdateRemoveFromGitignore()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();
        touch($dir.'/.gitignore');

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/unmanaged-plugin\n",
            "/wp-content/plugins/removed-plugin\n",
        ], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/unmanaged-plugin\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', [
            ['themes', []],
            ['plugins', [
                ['name' => 'removed-plugin'],
            ]],
        ]);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateBubbleErrors()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $factory = $this->getFactory([
        ], [
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::err('a WhippetJson error')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('whippet.json: a WhippetJson error', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testUpdateNoExistingWhippetLock()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'ref' => 'v1.6',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, false);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
    }

    public function testUpdateWithBrokenJson()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'ref' => 'v1.6',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], false, false);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
            ['\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson)],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock)],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, new \Dxw\Whippet\ProjectDirectory($dir));

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('missing sources', $result->getErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }
}
