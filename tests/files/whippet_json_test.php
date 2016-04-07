<?php

class Files_WhippetJson_Test extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    public function testGetDependenciesPlugins()
    {
        $whippetJson = new \Dxw\Whippet\Files\WhippetJson([
            'plugins' => [
                ['name' => 'advanced-custom-fields'],
            ],
        ]);

        $this->assertEquals([
            ['name' => 'advanced-custom-fields'],
        ], $whippetJson->getDependencies('plugins'));
    }

    public function testGetDependenciesBlank()
    {
        $whippetJson = new \Dxw\Whippet\Files\WhippetJson([]);

        $this->assertEquals([], $whippetJson->getDependencies('plugins'));
    }

    public function testGetSources()
    {
        $whippetJson = new \Dxw\Whippet\Files\WhippetJson([
            'src' => [
                'plugins' => 'git@git.dxw.net:wordpress-plugins/',
            ],
        ]);

        $this->assertEquals([
            'plugins' => 'git@git.dxw.net:wordpress-plugins/',
        ], $whippetJson->getSources());
    }
}
