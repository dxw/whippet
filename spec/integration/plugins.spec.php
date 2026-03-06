<?php

describe('Integration: plugins', function () {
	beforeEach(function () {
		$this->testDir = sys_get_temp_dir() . '/whippet-plugins-test-' . uniqid();
		mkdir($this->testDir, 0777, true);
		mkdir($this->testDir . '/wp-content/plugins', 0777, true);
		mkdir($this->testDir . '/config', 0777, true);
		file_put_contents($this->testDir . '/.gitignore', "\n");

		// Create a plugin git repo
		$this->gitRepoDir = $this->testDir . '/git-repo/advanced-custom-fields';
		mkdir($this->gitRepoDir, 0777, true);
		shell_exec("cd {$this->gitRepoDir} && git init && git config user.email \"you@example.com\" && git config user.name \"Your Name\" && git commit --allow-empty -m Meow");

		$this->whippetBin = realpath(__DIR__ . '/../../bin/whippet');
	});

	afterEach(function () {
		shell_exec("rm -rf {$this->testDir}");
	});

	$this->runWhippet = function ($command, $cwd) {
		$process = proc_open("{$this->whippetBin} {$command}", [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		], $pipes, $cwd);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$return = proc_close($process);

		return (object) [
			'return' => $return,
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
	};

	it('supports semicolon comment syntax in plugins file', function () {
		file_put_contents($this->testDir . '/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n; a good comment\n");

		$result = $this->runWhippet('plugins install', $this->testDir);

		expect($result->return)->toEqual(0);
		expect($result->stderr)->not->toMatch('/PHP (Fatal error|Warning|Notice|Deprecated)/');
	});

	it('fails with hash comment syntax in plugins file', function () {
		file_put_contents($this->testDir . '/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n# a bad comment\n");

		$result = $this->runWhippet('plugins install', $this->testDir);

		expect($result->return)->toEqual(1);
		expect($result->stderr)->not->toMatch('/PHP (Fatal error|Warning|Notice|Deprecated)/');
	});

	it('fails with hash comment syntax preceded by whitespace in plugins file', function () {
		file_put_contents($this->testDir . '/plugins', "source = \"git-repo/\"\nadvanced-custom-fields=\n # a bad comment\n");

		$result = $this->runWhippet('plugins install', $this->testDir);

		expect($result->return)->toEqual(1);
		expect($result->stderr)->not->toMatch('/PHP (Fatal error|Warning|Notice|Deprecated)/');
	});
});
