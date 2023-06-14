<?php

class PluginsTest extends \PHPUnit\Framework\TestCase
{
	public function cmd($cmd, $cwd = null)
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

	public function createTestDir()
	{
		# Create Whippet repo
		$this->dir = $dir = 'tests/plugins-test-dir';
		exec('rm -rf '.$dir);
		mkdir($dir);
		mkdir($dir.'/config');
		mkdir($dir.'/wp-content');
		mkdir($dir.'/wp-content/plugins');
		file_put_contents($dir.'/.gitignore', "\n");
		file_put_contents($dir.'/plugins', '');

		# Create a plugin git repo
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

		list($return, $stdout, $stderr) = $this->cmd('../../bin/whippet plugins install', dirname(__DIR__).'/'.$this->dir);

		$this->assertEquals(0, $return);

		$this->assertNotContains('PHP Fatal error', $stderr);
		$this->assertNotContains('PHP Warning', $stderr);
		$this->assertNotContains('PHP Notice', $stderr);
		$this->assertNotContains('PHP Deprecated', $stderr);
	}

	public function testDeprecatedCommentSyntax()
	{
		$this->createTestDir();
		file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n# a bad comment\n");

		list($return, $stdout, $stderr) = $this->cmd('../../bin/whippet plugins install', dirname(__DIR__).'/'.$this->dir);

		$this->assertEquals(1, $return);

		$this->assertNotContains('PHP Fatal error', $stderr);
		$this->assertNotContains('PHP Warning', $stderr);
		$this->assertNotContains('PHP Notice', $stderr);
		$this->assertNotContains('PHP Deprecated', $stderr);
	}

	public function testDeprecatedCommentSyntax2()
	{
		// Add whitespace before the #
		$this->createTestDir();
		file_put_contents($this->dir.'/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n # a bad comment\n");

		list($return, $stdout, $stderr) = $this->cmd('../../bin/whippet plugins install', dirname(__DIR__).'/'.$this->dir);

		$this->assertEquals(1, $return);

		$this->assertNotContains('PHP Fatal error', $stderr);
		$this->assertNotContains('PHP Warning', $stderr);
		$this->assertNotContains('PHP Notice', $stderr);
		$this->assertNotContains('PHP Deprecated', $stderr);
	}
}
