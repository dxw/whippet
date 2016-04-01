<?php

class DependenciesUpdater_Test extends PHPUnit_Framework_TestCase
{
    use Helpers;

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

        $factory = $this->getFactory([
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
