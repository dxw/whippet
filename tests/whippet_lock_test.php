<?php

class WhippetLock_Test extends PHPUnit_Framework_TestCase
{
    public function testGetDependencies()
    {
        $whippetLock = new \Dxw\Whippet\WhippetLock([
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
        $whippetLock = \Dxw\Whippet\WhippetLock::fromString(json_encode([
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

        $whippetLock = \Dxw\Whippet\WhippetLock::fromFile($dir.'/whippet.lock');

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
        $whippetLock = new \Dxw\Whippet\WhippetLock([
            'hash' => '123',
        ]);

        $this->assertEquals('123', $whippetLock->getHash());
    }
}