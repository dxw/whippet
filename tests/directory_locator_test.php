<?php

class DirectoryLocator_Test extends PHPUnit_Framework_TestCase
{
    public function testGetDirectorySuccess1()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        mkdir($dir.'/wp-content/themes/my-theme');
        touch($dir.'/whippet.json');

        foreach ([
            $dir.'/wp-content/themes/my-theme',
            $dir.'/wp-content/themes',
            $dir.'/wp-content',
            $dir,
        ] as $path) {
            $fileLocator = new \Dxw\Whippet\DirectoryLocator($path);
            $result = $fileLocator->getDirectory();
            $this->assertFalse($result->isErr());
            $this->assertEquals($dir, $result->unwrap());
        }
    }

    public function testGetDirectorySuccess2()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

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
            $fileLocator = new \Dxw\Whippet\DirectoryLocator($path);
            $result = $fileLocator->getDirectory();
            $this->assertFalse($result->isErr());
            $this->assertEquals($dir.'/projects/project1', $result->unwrap());
        }
    }

    public function testGetDirectoryFailure()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

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
            $fileLocator = new \Dxw\Whippet\DirectoryLocator($path);
            $result = $fileLocator->getDirectory();
            $this->assertTrue($result->isErr());
            $this->assertEquals('whippet.json not found', $result->getErr());
        }
    }

    public function testGetDirectoryWhippetJson()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        mkdir($dir.'/wp-content/themes/my-theme');
        touch($dir.'/whippet.json');

        foreach ([
            $dir.'/wp-content/themes/my-theme',
            $dir.'/wp-content/themes',
            $dir.'/wp-content',
            $dir,
        ] as $path) {
            $fileLocator = new \Dxw\Whippet\DirectoryLocator($path);
            $result = $fileLocator->getDirectory();
            $this->assertFalse($result->isErr());
            $this->assertEquals($dir, $result->unwrap());
        }
    }

    public function testGetDirectoryAvoidPluginsDirectory()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();
        $dir = $root->url();

        mkdir($dir.'/wp-content');
        mkdir($dir.'/wp-content/plugins');
        mkdir($dir.'/wp-content/plugins/my-plugin');
        touch($dir.'/whippet.json');

        $fileLocator = new \Dxw\Whippet\DirectoryLocator($dir.'/wp-content/plugins/my-plugin');
        $result = $fileLocator->getDirectory();
        $this->assertFalse($result->isErr());
        $this->assertEquals($dir, $result->unwrap());
    }
}
