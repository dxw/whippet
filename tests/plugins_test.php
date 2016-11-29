<?php

class PluginsTest extends PHPUnit_Framework_TestCase
{
    public function SetUp()
    {
        $this->dir = 'tests/plugins-test-dir';
        mkdir($this->dir);
    }

    public function TearDown()
    {
        exec('rm -rf '.$this->dir);
    }

    private function cmd($cmd, $cwd = null)
    {
        $process = proc_open($cmd, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $cwd);

        $this->assertTrue(is_resource($process));

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $return = proc_close($process);

        return [$return, $stdout, $stderr];
    }

    private function whippetPluginsInstallCmd()
    {
        return $this->cmd('../../bin/whippet plugins install', dirname(__DIR__).'/'.$this->dir);
    }

    private function createTestDir()
    {
        $this->createWhippetRepo($this->dir);
        $this->createPluginGitRepo($this->dir);
    }

    private function createWhippetRepo($dir)
    {
        mkdir($dir.'/config');
        mkdir($dir.'/wp-content');
        mkdir($dir.'/wp-content/plugins');
        file_put_contents($dir.'/.gitignore', "\n");
    }

    private function createPluginGitRepo($dir)
    {
        mkdir($dir.'/git-repo');
        mkdir($dir.'/git-repo/advanced-custom-fields');
        list($return, $stdout, $stderr) = $this->cmd('git init', $dir.'/git-repo/advanced-custom-fields');
        $this->assertEquals(0, $return, 'Error running git command');
        list($return, $stdout, $stderr) = $this->cmd('git commit --allow-empty -m Meow', $dir.'/git-repo/advanced-custom-fields');
        $this->assertEquals(0, $return, 'Error running git command');
    }

    public function testSupportedCommentSyntax()
    {
        $this->createTestDir();
        file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n; a good comment\n");

        list($return, $stdout, $stderr) = $this->whippetPluginsInstallCmd();

        $this->assertEquals(0, $return);
        $this->assertContains('The plugins subcommand is deprecated and will be removed in a future release', $stdout);
        $this->assertNoErrors($stderr);
    }

    public function testDeprecatedCommentSyntax()
    {
        $this->createTestDir();
        file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n# a bad comment\n");

        list($return, $stdout, $stderr) = $this->whippetPluginsInstallCmd();

        $this->assertEquals(1, $return);
        $this->assertNoErrors($stderr);
    }

    public function testDeprecatedCommentSyntax2()
    {
        // Add whitespace before the #
        $this->createTestDir();
        file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n # a bad comment\n");

        list($return, $stdout, $stderr) = $this->whippetPluginsInstallCmd();

        $this->assertEquals(1, $return);
        $this->assertNoErrors($stderr);
    }

    private function assertNoErrors($output)
    {
        $this->assertNotContains('PHP Fatal error', $output);
        $this->assertNotContains('PHP Warning', $output);
        $this->assertNotContains('PHP Notice', $output);
        $this->assertNotContains('PHP Deprecated', $output);
    }
}
