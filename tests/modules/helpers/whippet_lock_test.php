<?php

class Modules_Helpers_WhippetLock_Test extends PHPUnit_Framework_TestCase
{
    public function testGetDependencies()
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

        $whippetLock = new \Dxw\Whippet\Modules\Helpers\WhippetLock($dir.'/whippet.lock');

        $this->assertEquals([
            [
                'name' => 'my-theme',
                'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                'revision' => '27ba906',
            ],
        ], $whippetLock->getDependencies('themes'));
    }
}
