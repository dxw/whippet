<?php

namespace Dxw\Whippet\Tests;

class UpdateDependenciesTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dir = 'tests/tmp/deps-test';
        mkdir($this->dir);
    }

    public function tearDown()
    {
        exec('rm -rf '.$this->dir);
    }

    public function testValidLockfile()
    {
        $lockfile = <<<EOT
{
    "hash": "a5e744d0164540d33b1d7ea616c28f2fa97e754a",
    "plugins": [
         {
              "name": "akismet",
              "src": "git@git.dxw.net:wordpress-plugins/akismet",
              "revision": "9ca38d7b3e345affc3afc07c4c09598d41c9543b"
         }
    ]
}
EOT;
        $this->createTestDir();
        file_put_contents($this->dir.'/whippet.json', '{"foo":"bar"}');
        file_put_contents($this->dir.'/whippet.lock', $lockfile);

        list($return, $stdout, $stderr) = $this->whippetDepsInstallCmd();

        $this->assertContains('[Adding plugins/akismet]', $stdout);
        $this->assertNoErrors($stderr);
        $this->assertEquals(0, $return);
    }

    public function testLockfileWithIncorrectHash()
    {
        $lockfile = <<<EOT
{
    "hash": "8843d7f92416211de9ebb963ff4ce28125932878",
    "plugins": [
         {
              "name": "akismet",
              "src": "git@git.dxw.net:wordpress-plugins/akismet",
              "revision": "9ca38d7b3e345affc3afc07c4c09598d41c9543b"
         }
    ]
}
EOT;
        $this->createTestDir();
        file_put_contents($this->dir.'/whippet.json', '{"foo":"bar"}');
        file_put_contents($this->dir.'/whippet.lock', $lockfile);

        list($return, $stdout, $stderr) = $this->whippetDepsInstallCmd();

        $this->assertContains('ERROR: mismatched hash - run `whippet dependencies update` first', $stdout);
        $this->assertNoErrors($stderr);
        $this->assertEquals(1, $return);
    }

    public function testInvalidLockfile()
    {
        $this->createTestDir();
        file_put_contents($this->dir.'/whippet.json', '');
        file_put_contents($this->dir.'/whippet.lock', 'foo');

        list($return, $stdout, $stderr) = $this->whippetDepsInstallCmd();

        $this->assertContains('ERROR: whippet.lock: invalid JSON', $stdout);
        $this->assertNoErrors($stderr);
        $this->assertEquals(1, $return);
    }

    public function testMissingLockfile()
    {
        $this->createTestDir();
        file_put_contents($this->dir.'/whippet.json', '');

        list($return, $stdout, $stderr) = $this->whippetDepsInstallCmd();

        $this->assertContains('ERROR: whippet.lock: file not found', $stdout);
        $this->assertNoErrors($stderr);
        $this->assertEquals(1, $return);
    }

    private function assertNoErrors($output)
    {
        $this->assertNotContains('PHP Fatal error', $output);
        $this->assertNotContains('PHP Warning', $output);
        $this->assertNotContains('PHP Notice', $output);
        $this->assertNotContains('PHP Deprecated', $output);
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

    private function whippetDepsInstallCmd()
    {
        $project_root = '../../..';
        return $this->cmd($project_root.'/bin/whippet deps update', $this->dir);
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
}
