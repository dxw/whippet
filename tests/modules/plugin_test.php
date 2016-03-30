<?php

class ModulesPluginTest extends PHPUnit_Framework_TestCase
{
    private function createTestDir()
    {
        $root = \org\bovigo\vfs\vfsStream::setup();

        # Create Whippet repo
        $this->dir = $dir = $root->url();
        mkdir($dir.'/config');
        mkdir($dir.'/wp-content');
        mkdir($dir.'/wp-content/plugins');
        file_put_contents($dir.'/.gitignore', "\n");
        file_put_contents($dir.'/plugins', '');

        # Create a plugin git repo
        mkdir($dir.'/git-repo');
        mkdir($dir.'/git-repo/advanced-custom-fields');
        // list($return, $stdout, $stderr) = $this->cmd('git init', $dir.'/git-repo/advanced-custom-fields');
        // $this->assertEquals(0, $return, 'Error running git command');
        // list($return, $stdout, $stderr) = $this->cmd('git commit --allow-empty -m Meow', $dir.'/git-repo/advanced-custom-fields');
        // $this->assertEquals(0, $return, 'Error running git command');
    }

    public function testSupportedCommentSyntax()
    {
        $untestable = $this->getMockBuilder('\\Dxw\\Whippet\\Untestable')
        ->setMethods(['_exit', '_die'])
        ->getMock();

        $this->createTestDir();
        file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n; a good comment\n");

        $plugin = new \Dxw\Whippet\Modules\Plugin($this->dir, $untestable);

        ob_start();
        $plugin->start(['plugins', 'install']);
        $output = ob_get_clean();

        $this->assertNotContains('PHP Fatal error', $output);
        $this->assertNotContains('PHP Warning', $output);
        $this->assertNotContains('PHP Notice', $output);
        $this->assertNotContains('PHP Deprecated', $output);
    }

    // public function testDeprecatedCommentSyntax()
    // {
    //     $this->createTestDir();
    //     file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n# a bad comment\n");

    //     list($return, $stdout, $stderr) = $this->cmd('../../bin/whippet plugins install', dirname(__DIR__).'/'.$this->dir);

    //     $this->assertEquals(1, $return);

    //     $this->assertNotContains('PHP Fatal error', $stderr);
    //     $this->assertNotContains('PHP Warning', $stderr);
    //     $this->assertNotContains('PHP Notice', $stderr);
    //     $this->assertNotContains('PHP Deprecated', $stderr);
    // }

    // public function testDeprecatedCommentSyntax2()
    // {
    //     // Add whitespace before the #
    //     $this->createTestDir();
    //     file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n # a bad comment\n");

    //     list($return, $stdout, $stderr) = $this->cmd('../../bin/whippet plugins install', dirname(__DIR__).'/'.$this->dir);

    //     $this->assertEquals(1, $return);

    //     $this->assertNotContains('PHP Fatal error', $stderr);
    //     $this->assertNotContains('PHP Warning', $stderr);
    //     $this->assertNotContains('PHP Notice', $stderr);
    //     $this->assertNotContains('PHP Deprecated', $stderr);
    // }
}
