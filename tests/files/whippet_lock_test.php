<?php

class Files_WhippetLock_Test extends PHPUnit_Framework_TestCase
{
    public function testGetDependencies()
    {
        $whippetLock = new \Dxw\Whippet\Files\WhippetLock([
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ]);

        $this->assertEquals([
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ], $whippetLock->getDependencies('themes'));
    }

    public function testFromStringGetDependencies()
    {
        $whippetLock = \Dxw\Whippet\Files\WhippetLock::fromString(json_encode([
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ]));

        $this->assertEquals([
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ], $whippetLock->getDependencies('themes'));
    }

    public function testFromFileGetDependencies()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        file_put_contents($dir.'/whippet.lock', json_encode([
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ]));

        $whippetLock = \Dxw\Whippet\Files\WhippetLock::fromFile($dir.'/whippet.lock');

        $this->assertEquals([
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ], $whippetLock->getDependencies('themes'));
    }

    public function testGetHash()
    {
        $whippetLock = new \Dxw\Whippet\Files\WhippetLock([
            'hash' => '123',
        ]);

        $this->assertEquals('123', $whippetLock->getHash());
    }

    public function testGetDependenciesNotSet()
    {
        $whippetLock = new \Dxw\Whippet\Files\WhippetLock([
            'themes' => [],
        ]);

        $this->assertEquals([], $whippetLock->getDependencies('plugins'));
    }

    public function testSetHash()
    {
        $whippetLock = new \Dxw\Whippet\Files\WhippetLock([]);

        $whippetLock->setHash('123');

        $this->assertEquals('123', $whippetLock->getHash());
    }

    public function testAddDependency()
    {
        $whippetLock = new \Dxw\Whippet\Files\WhippetLock([]);

        $whippetLock->addDependency('plugins', 'my-plugin', 'git@git.dxw.net:foobar/baz', '123abc');
        $this->assertEquals([
            [
                'name' => 'my-plugin',
                'src' => 'git@git.dxw.net:foobar/baz',
                'revision' => '123abc',
            ],
        ], $whippetLock->getDependencies('plugins'));
    }

    public function testSaveToPath()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $data = [
            'foo' => 'bar',
        ];

        $whippetLock = new \Dxw\Whippet\Files\WhippetLock($data);

        $whippetLock->saveToPath($dir.'/my-whippet.lock');

        $this->assertTrue(file_exists($dir.'/my-whippet.lock'));
        $this->assertEquals($data, json_decode(file_get_contents($dir.'/my-whippet.lock'), true));
    }
}
