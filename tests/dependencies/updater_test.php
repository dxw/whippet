<?php

class Dependencies_Updater_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

    private function getGitignore(array $get, array $save, /* bool */ $saveIgnores)
    {
        $gitignore = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Gitignore')
        ->disableOriginalConstructor()
        ->getMock();

        $gitignore->method('get_ignores')
        ->willReturn($get);

        $gitignore->expects($this->exactly($saveIgnores ? 1 : 0))
        ->method('save_ignores')
        ->with($save);

        return $gitignore;
    }

    private function getWhippetLock(array $addDependency, /* string */ $hash, /* string */ $path)
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

        return $whippetLock;
    }

    public function testUpdate()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $json = json_encode([
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

        file_put_contents($dir.'/whippet.json', $json);

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
            "/wp-content/plugins/my-plugin\n",
        ], true);

        $whippetLock = $this->getWhippetLock([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
            ['plugins', 'my-plugin', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'd961c3d'],
        ], sha1($json), $dir.'/whippet.lock');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

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

        $json = json_encode([
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

        file_put_contents($dir.'/whippet.json', $json);

        $gitignore = $this->getGitignore([
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
        ], [
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
            "/wp-content/themes/my-theme\n",
        ], true);

        $whippetLock = $this->getWhippetLock([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1($json), $dir.'/whippet.lock');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

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

        $json = json_encode([
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

        file_put_contents($dir.'/whippet.json', $json);

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
        ], true);

        $whippetLock = $this->getWhippetLock([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1($json), $dir.'/whippet.lock');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

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

        $json = json_encode([
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

        file_put_contents($dir.'/whippet.json', $json);

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], false);

        $whippetLock = $this->getWhippetLock([], sha1($json), null);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::err('oh no')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

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

        $json = json_encode([
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

        file_put_contents($dir.'/whippet.json', $json);

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], true);

        $whippetLock = $this->getWhippetLock([
            ['themes', 'my-theme', 'foobar', '27ba906'],
        ], sha1($json), $dir.'/whippet.lock');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'foobar', 'v1.4', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

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

        $json = json_encode([
            'src' => [
                'themes' => 'git@git.dxw.net:wordpress-themes/',
            ],
            'themes' => [
                [
                    'name' => 'my-theme',
                ],
            ],
        ]);

        file_put_contents($dir.'/whippet.json', $json);

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ], true);

        $whippetLock = $this->getWhippetLock([
            ['themes', 'my-theme', 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906'],
        ], sha1($json), $dir.'/whippet.lock');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'master', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

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

        $json = json_encode([]);

        file_put_contents($dir.'/whippet.json', $json);

        $gitignore = $this->getGitignore([], [], true);

        $whippetLock = $this->getWhippetLock([], sha1($json), $dir.'/whippet.lock');

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
            ['\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock],
        ], [
        ]);

        $dependencies = new \Dxw\Whippet\Dependencies\Updater($factory, $dir);

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("whippet.json contains no dependencies\n", $output);
    }
}
