<?php

class Files_WhippetLock_Test extends \PHPUnit\Framework\TestCase
{
	use \Helpers;

	public function testGetDependencies()
	{
		$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
			'themes' => [
				[
					'name' => 'my-theme',
					'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
					'revision' => '27ba906',
				],
			],
		]);

		$this->assertEquals([
			[
				'name' => 'my-theme',
				'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
				'revision' => '27ba906',
			],
		], $whippetLock->getDependencies('themes'));
	}

	public function testFromStringGetDependencies()
	{
		$whippetLock = \Dxw\Whippet\Files\WhippetLock::fromString(json_encode([
			'themes' => [
				[
					'name' => 'my-theme',
					'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
					'revision' => '27ba906',
				],
			],
		]));

		$this->assertFalse($whippetLock->isErr());
		$this->assertEquals([
			[
				'name' => 'my-theme',
				'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
				'revision' => '27ba906',
			],
		], $whippetLock->unwrap()->getDependencies('themes'));
	}

	public function testFromFileGetDependencies()
	{
		$dir = $this->getDir();

		file_put_contents($dir.'/whippet.lock', json_encode([
			'themes' => [
				[
					'name' => 'my-theme',
					'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
					'revision' => '27ba906',
				],
			],
		]));

		$whippetLock = \Dxw\Whippet\Files\WhippetLock::fromFile($dir.'/whippet.lock');

		$this->assertFalse($whippetLock->isErr());
		$this->assertEquals([
			[
				'name' => 'my-theme',
				'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
				'revision' => '27ba906',
			],
		], $whippetLock->unwrap()->getDependencies('themes'));
	}

	public function testGetHash()
	{
		$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
			'hash' => '123',
		]);

		$this->assertEquals('123', $whippetLock->getHash());
	}

	public function testGetDependenciesNotSet()
	{
		$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
			'themes' => [],
		]);

		$this->assertEquals([], $whippetLock->getDependencies('plugins'));
	}

	public function testSetHash()
	{
		$whippetLock = new \Dxw\Whippet\Files\WhippetLock([]);

		$whippetLock->setHash('123');

		$this->assertEquals('123', $whippetLock->getHash());
	}

	public function testAddDependency()
	{
		$whippetLock = new \Dxw\Whippet\Files\WhippetLock([]);

		$whippetLock->addDependency('plugins', 'my-plugin', 'git@git.govpress.com:foobar/baz', '123abc');
		$this->assertEquals([
			[
				'name' => 'my-plugin',
				'src' => 'git@git.govpress.com:foobar/baz',
				'revision' => '123abc',
			],
		], $whippetLock->getDependencies('plugins'));
	}

	public function testAddDependencyThatAlreadyExists()
	{
		$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
			'plugins' => [
				[
					'name' => 'my-other-plugin',
					'src' => 'git@git.govpress.com:foobar/bat',
					'revision' => 'zzz',
				],
				[
					'name' => 'my-plugin',
					'src' => 'git@git.govpress.com:foobar/baz',
					'revision' => '456789',
				],
			],
		]);

		$whippetLock->addDependency('plugins', 'my-plugin', 'git@git.govpress.com:foobar/baz', '123abc');
		$this->assertEquals([
			[
				'name' => 'my-other-plugin',
				'src' => 'git@git.govpress.com:foobar/bat',
				'revision' => 'zzz',
			],
			[
				'name' => 'my-plugin',
				'src' => 'git@git.govpress.com:foobar/baz',
				'revision' => '123abc',
			],
		], $whippetLock->getDependencies('plugins'));
	}

	public function testSaveToPath()
	{
		$dir = $this->getDir();

		$data = [
			'foo' => 'bar',
		];

		$whippetLock = new \Dxw\Whippet\Files\WhippetLock($data);

		$whippetLock->saveToPath($dir.'/my-whippet.lock');

		$this->assertTrue(file_exists($dir.'/my-whippet.lock'));
		$this->assertEquals($data, json_decode(file_get_contents($dir.'/my-whippet.lock'), true));
	}

	public function testSaveToPathPrettyPrinting()
	{
		$dir = $this->getDir();

		$data = [
			'foo' => '/',
		];

		$whippetLock = new \Dxw\Whippet\Files\WhippetLock($data);

		$whippetLock->saveToPath($dir.'/my-whippet.lock');

		$this->assertTrue(file_exists($dir.'/my-whippet.lock'));
		$this->assertEquals(implode("\n", [
			'{',
			'    "foo": "/"',
			'}',
			'', // Trailing newline
		]), file_get_contents($dir.'/my-whippet.lock'), true);
	}

	public function testFromStringInvalid()
	{
		$output = \Dxw\Whippet\Files\WhippetLock::fromString('this is not json');

		$this->assertTrue($output->isErr());
		$this->assertEquals('invalid JSON', $output->getErr());
	}

	public function testFromFileNotFound()
	{
		$dir = $this->getDir();

		$output = \Dxw\Whippet\Files\WhippetLock::fromFile($dir.'/file-not-found.json');

		$this->assertTrue($output->isErr());
		$this->assertEquals('file not found', $output->getErr());
	}
}
