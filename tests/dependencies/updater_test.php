<?php

class Dependencies_Updater_Test extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    private function getGitignore(array $get, array $save, /* bool */ $saveIgnores, /* bool */ $warnOnGet)
    {
        $gitignore = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Gitignore')
        ->disableOriginalConstructor()
        ->getMock();

        $getIgnores = $gitignore->method('get_ignores');
        if ($warnOnGet) {
            $getIgnores->will($this->returnCallback(function () {
                trigger_error('$warOnGet set but not prevented', E_USER_WARNING);
            }));
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

    public function testUpdateAll()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
    }

    public function testUpdateAllWithExistingGitignore()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

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
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateAllWithExistingGitignoreNoDuplication()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

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
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateAllFailedGitCommand()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], false, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::err('oh no'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('git command failed: oh no', $result->getErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateAllWithExplicitSrc()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'foobar', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'foobar', 'v1.4', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateAllWithoutRef()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'master', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateAllBlankJsonfile()
    {
        $dir = $this->getDir();

        $whippetJson = $this->getWhippetJson([]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("whippet.json contains no dependencies\n", $output);
    }

    public function testUpdateAllNoGitignore()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, true);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
    }

    public function testUpdateAllRemoveFromGitignore()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/unmanaged-plugin\n",
            "/wp-content/plugins/removed-plugin\n",
        ], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/unmanaged-plugin\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', [
            ['themes', []],
            ['plugins', [
                ['name' => 'removed-plugin'],
            ]],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateAllBubbleErrors()
    {
        $dir = $this->getDir();

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::err('a WhippetJson error'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('whippet.json: a WhippetJson error', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testUpdateAllNoExistingWhippetLock()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock);

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
    }

    public function testUpdateAllWithBrokenJson()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], false, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('missing sources', $result->getErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleWithNoLock()
    {
        $dir = $this->getDir();

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('twitget');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals("No whippet.lock file exists, you need to run `whippet deps update` to generate one before you can update a specific dependency. \n", $output);
        $this->assertEquals('whippet.lock: file not found', $result->getErr());
    }

    public function testUpdateSingleIncorrectFormat()
    {
        $dir = $this->getDir();

        file_put_contents($dir.'/whippet.json', 'foobar');

        $whippetLock = [];
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('twitget');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals("Dependency should be in format [type]/[name]. \n", $output);
        $this->assertEquals('Incorrect dependency format', $result->getErr());
    }

    public function testUpdateSingleNoMatch()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));


        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('plugins/twitget');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('No matching dependency in whippet.json', $result->getErr());
    }

    public function testUpdateSingleBrokenJson()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], false, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('missing sources', $result->getErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleWithExistingGitignore()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

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
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleWithExistingGitignoreNoDuplication()
    {
        $dir = $this->getDir();
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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

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
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleFailedGitCommand()
    {
        $dir = $this->getDir();

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
            'plugins' => [
                [
                    'name' => 'twitget',
                ],
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], false, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::err('oh no'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('git command failed: oh no', $result->getErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleWithExplicitSrc()
    {
        $dir = $this->getDir();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                    'ref' => 'v1.4',
                    'src' => 'foobar',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'twitget',
                    'ref' => 'v1.4',
                    'src' => 'foobar',
                ],
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/twitget\n"
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'foobar', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'foobar', 'v1.4', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleWithoutRef()
    {
        $dir = $this->getDir();

        $whippetJson = $this->getWhippetJson([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                ],
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'master', \Result\Result::ok('27ba906'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }

    public function testUpdateSingleNoGitignore()
    {
        $dir = $this->getDir();

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        file_put_contents($dir.'/whippet.json', 'foobar');

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, true);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $whippetLock = $this->getWhippetLockWritable([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);
    }


    public function testUpdateSingle()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');

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
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

        $whippetLock = $this->getWhippetLockWritable([
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1('foobar'), $dir.'/whippet.lock', []);

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

        $gitignore = $this->getGitignore(["/wp-content/themes/my-theme\n",
        "/wp-content/plugins/my-plugin\n", ], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true, false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater(
            $this->getFactory(),
            $this->getProjectDirectory($dir)
        );

        ob_start();
        $result = $dependencies->updateSingle('plugins/my-plugin');
        $output = ob_get_clean();

        $this->assertEquals("[Updating plugins/my-plugin]\n", $output);
        $this->assertFalse($result->isErr());
    }
}
