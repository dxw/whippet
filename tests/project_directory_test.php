<?php

class ProjectDirectory_Test extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    public function tearDown()
    {
        putenv('WHIPPET_PROJECT_DIRECTORY=');
    }

    public function testGetDirectorySuccess1()
    {
        $dir = $this->getDir();

        mkdir($dir.'/wp-content/themes/my-theme');
        touch($dir.'/whippet.json');

        foreach ([
            $dir.'/wp-content/themes/my-theme',
            $dir.'/wp-content/themes',
            $dir.'/wp-content',
            $dir,
        ] as $path) {
            $result = \Dxw\Whippet\ProjectDirectory::find($path);
            $this->assertFalse($result->isErr());
            $this->assertInstanceOf('\\Dxw\\Whippet\\ProjectDirectory', $result->unwrap());
            $this->assertEquals($dir, $result->unwrap()->__toString());
        }
    }

    public function testGetDirectorySuccess2()
    {
        $dir = $this->getDir();

        mkdir($dir.'/projects');
        mkdir($dir.'/projects/project1');
        mkdir($dir.'/projects/project1/wp-content/themes/my-theme');
        mkdir($dir.'/projects/project1/wp-content');
        mkdir($dir.'/projects/project1/wp-content/themes');
        mkdir($dir.'/projects/project1/wp-content/themes/my-theme');
        touch($dir.'/projects/project1/whippet.json');

        foreach ([
            $dir.'/projects/project1/wp-content/themes/my-theme',
            $dir.'/projects/project1/wp-content/themes',
            $dir.'/projects/project1/wp-content',
            $dir.'/projects/project1',
        ] as $path) {
            $result = \Dxw\Whippet\ProjectDirectory::find($path);
            $this->assertFalse($result->isErr());
            $this->assertInstanceOf('\\Dxw\\Whippet\\ProjectDirectory', $result->unwrap());
            $this->assertEquals($dir.'/projects/project1', $result->unwrap()->__toString());
        }
    }

    public function testGetDirectoryFailure()
    {
        $dir = $this->getDir();

        mkdir($dir.'/projects');
        mkdir($dir.'/projects/project1');
        mkdir($dir.'/projects/project1/wp-content/themes/my-theme');
        mkdir($dir.'/projects/project1/wp-content');
        mkdir($dir.'/projects/project1/wp-content/themes');
        mkdir($dir.'/projects/project1/wp-content/themes/my-theme');
        touch($dir.'/plugins');

        foreach ([
            $dir.'/projects/project1/wp-content/themes/my-theme',
            $dir.'/projects/project1/wp-content/themes',
            $dir.'/projects/project1/wp-content',
            $dir.'/projects/project1',
        ] as $path) {
            $result = \Dxw\Whippet\ProjectDirectory::find($path);
            $this->assertTrue($result->isErr());
            $this->assertEquals('whippet.json not found', $result->getErr());
        }
    }

    public function testGetDirectoryWhippetJson()
    {
        $dir = $this->getDir();

        mkdir($dir.'/wp-content/themes/my-theme');
        touch($dir.'/whippet.json');

        foreach ([
            $dir.'/wp-content/themes/my-theme',
            $dir.'/wp-content/themes',
            $dir.'/wp-content',
            $dir,
        ] as $path) {
            $result = \Dxw\Whippet\ProjectDirectory::find($path);
            $this->assertFalse($result->isErr());
            $this->assertInstanceOf('\\Dxw\\Whippet\\ProjectDirectory', $result->unwrap());
            $this->assertEquals($dir, $result->unwrap()->__toString());
        }
    }

    public function testGetDirectoryAvoidPluginsDirectory()
    {
        $dir = $this->getDir();

        mkdir($dir.'/wp-content');
        mkdir($dir.'/wp-content/plugins');
        mkdir($dir.'/wp-content/plugins/my-plugin');
        touch($dir.'/whippet.json');

        $result = \Dxw\Whippet\ProjectDirectory::find($dir.'/wp-content/plugins/my-plugin');
        $this->assertFalse($result->isErr());
        $this->assertInstanceOf('\\Dxw\\Whippet\\ProjectDirectory', $result->unwrap());
        $this->assertEquals($dir, $result->unwrap()->__toString());
    }

    public function testGetDirectoryWithEnv()
    {
        $dir = $this->getDir();

        putenv('WHIPPET_PROJECT_DIRECTORY=/etc');
        mkdir($dir.'/wp-content');
        mkdir($dir.'/wp-content/plugins');
        mkdir($dir.'/wp-content/plugins/my-plugin');
        touch($dir.'/whippet.json');

        $result = \Dxw\Whippet\ProjectDirectory::find($dir.'/wp-content/plugins/my-plugin');
        $this->assertFalse($result->isErr());
        $this->assertInstanceOf('\\Dxw\\Whippet\\ProjectDirectory', $result->unwrap());
        $this->assertEquals('/etc', $result->unwrap()->__toString());
    }
}
