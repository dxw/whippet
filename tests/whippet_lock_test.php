<?php

class WhippetLock_Test extends PHPUnit_Framework_TestCase
{
    public function testGetDependencies()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $data = [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ];

        $whippetLock = new \Dxw\Whippet\WhippetLock($data);

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
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        $json = json_encode([
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
        ]);

        $whippetLock = \Dxw\Whippet\WhippetLock::fromString($json);

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
}
