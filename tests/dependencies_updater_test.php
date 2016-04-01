<?php

class DependenciesUpdater_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

    private function getGitignore(array $get, array $save)
    {
        $gitignore = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Gitignore')
        ->disableOriginalConstructor()
        ->getMock();

        $gitignore->method('get_ignores')
        ->willReturn($get);

        $gitignore->expects($this->exactly(1))
        ->method('save_ignores')
        ->with($save);

        return $gitignore;
    }

    public function testUpdate()
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

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $gitignore = $this->getGitignore([], [
            "/wp-content/themes/my-theme\n",
        ]);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesUpdater($factory, $fileLocator);

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);

        $this->assertTrue(file_exists($dir.'/whippet.lock'));
        $this->assertEquals([
            'hash' => sha1($json),
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ], json_decode(file_get_contents($dir.'/whippet.lock'), true));
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

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

        $gitignore = $this->getGitignore([
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
        ], [
            "/wp-content/languages\n",
            "/node_modules\n",
            "/vendor\n",
            "/wp-content/themes/my-theme\n",
        ]);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesUpdater($factory, $fileLocator);

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);

        $this->assertTrue(file_exists($dir.'/whippet.lock'));
        $this->assertEquals([
            'hash' => sha1($json),
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ], json_decode(file_get_contents($dir.'/whippet.lock'), true));
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

        $fileLocator = $this->getFileLocator(\Result\Result::ok($dir));

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
        ]);

        $factory = $this->getFactory([
            ['\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore],
        ], [
            ['\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.dxw.net:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906')],
        ]);

        $dependencies = new \Dxw\Whippet\DependenciesUpdater($factory, $fileLocator);

        ob_start();
        $result = $dependencies->update();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Updating themes/my-theme]\n", $output);

        $this->assertTrue(file_exists($dir.'/whippet.lock'));
        $this->assertEquals([
            'hash' => sha1($json),
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ], json_decode(file_get_contents($dir.'/whippet.lock'), true));
    }
}
