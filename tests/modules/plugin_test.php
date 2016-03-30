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

    private function getFactory(array $instances)
    {
        $factory = $this->getMockBuilder('\\Dxw\\Whippet\\Factory')
        ->setMethods(['newInstance'])
        ->getMock();

        foreach ($instances as $instance) {
            call_user_func(
                [
                    call_user_func_array([$factory->method('newInstance'), 'with'], $instance['args']),
                    'willReturn',
                ],
                $instance['return']
            );
        }

        return $factory;
    }

    public function testSupportedCommentSyntax()
    {
        $this->createTestDir();

        $untestable = $this->getMockBuilder('\\Dxw\\Whippet\\Untestable')
        ->getMock();

        $git = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Git')
        ->disableOriginalConstructor()
        ->getMock();

        $factory = $this->getFactory([
            [
                'args' => ['\\Dxw\\Whippet\\Git\\Git', $this->dir.'/wp-content/plugins/advanced-custom-fields'],
                'return' => $git,
            ],
        ]);

        file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n; a good comment\n");

        $plugin = new \Dxw\Whippet\Modules\Plugin($this->dir, $untestable, $factory);

        ob_start();
        $plugin->install();
        $output = ob_get_clean();

        $this->assertNotContains('PHP Fatal error', $output);
        $this->assertNotContains('PHP Warning', $output);
        $this->assertNotContains('PHP Notice', $output);
        $this->assertNotContains('PHP Deprecated', $output);
        $this->assertContains('A default application.json was created', $output);
        $this->assertContains('[Adding advanced-custom-fields] Cloning into', $output);
        $this->assertContains('[Checking advanced-custom-fields-pro] Note: checking out', $output);
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
