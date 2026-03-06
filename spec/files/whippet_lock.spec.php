<?php

use org\bovigo\vfs\vfsStream;

describe(\Dxw\Whippet\Files\WhippetLock::class, function () {
	beforeEach(function () {
		$this->root = vfsStream::setup();
		$this->dir = $this->root->url();
	});

	describe('->getDependencies()', function () {
		it('returns dependencies when they exist', function () {
			$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
				'themes' => [
					[
						'name' => 'my-theme',
						'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
						'revision' => '27ba906',
					],
				],
			]);

			expect($whippetLock->getDependencies('themes'))->toEqual([
				[
					'name' => 'my-theme',
					'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
					'revision' => '27ba906',
				],
			]);
		});

		it('returns an empty array when no dependencies exist', function () {
			$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
				'themes' => [],
			]);

			expect($whippetLock->getDependencies('plugins'))->toEqual([]);
		});
	});

	describe('::fromString()', function () {
		it('returns a WhippetLock from a JSON string', function () {
			$json = json_encode([
				'themes' => [
					[
						'name' => 'my-theme',
						'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
						'revision' => '27ba906',
					],
				],
			]);

			$result = \Dxw\Whippet\Files\WhippetLock::fromString($json);

			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toBeAnInstanceOf(\Dxw\Whippet\Files\WhippetLock::class);
			expect($result->unwrap()->getDependencies('themes'))->toEqual([
				[
					'name' => 'my-theme',
					'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
					'revision' => '27ba906',
				],
			]);
		});

		it('returns an error if the JSON is invalid', function () {
			$result = \Dxw\Whippet\Files\WhippetLock::fromString('this is not json');

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual('invalid JSON');
		});
	});

	describe('::fromFile()', function () {
		it('returns a WhippetLock from a file', function () {
			file_put_contents($this->dir.'/whippet.lock', json_encode([
				'themes' => [
					[
						'name' => 'my-theme',
						'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
						'revision' => '27ba906',
					],
				],
			]));

			$result = \Dxw\Whippet\Files\WhippetLock::fromFile($this->dir.'/whippet.lock');

			expect($result->isErr())->toBe(false);
			expect($result->unwrap()->getDependencies('themes'))->toEqual([
				[
					'name' => 'my-theme',
					'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
					'revision' => '27ba906',
				],
			]);
		});

		it('returns an error if the file is not found', function () {
			$result = \Dxw\Whippet\Files\WhippetLock::fromFile($this->dir.'/file-not-found.json');

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual('file not found');
		});
	});

	describe('->getHash()', function () {
		it('returns the hash', function () {
			$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
				'hash' => '123',
			]);

			expect($whippetLock->getHash())->toEqual('123');
		});
	});

	describe('->setHash()', function () {
		it('sets the hash', function () {
			$whippetLock = new \Dxw\Whippet\Files\WhippetLock([]);

			$whippetLock->setHash('123');

			expect($whippetLock->getHash())->toEqual('123');
		});
	});

	describe('->addDependency()', function () {
		it('adds a new dependency', function () {
			$whippetLock = new \Dxw\Whippet\Files\WhippetLock([]);

			$whippetLock->addDependency('plugins', 'my-plugin', 'git@github.com/foobar/baz', '123abc');
			expect($whippetLock->getDependencies('plugins'))->toEqual([
				[
					'name' => 'my-plugin',
					'src' => 'git@github.com/foobar/baz',
					'revision' => '123abc',
				],
			]);
		});

		it('updates an existing dependency', function () {
			$whippetLock = new \Dxw\Whippet\Files\WhippetLock([
				'plugins' => [
					[
						'name' => 'my-other-plugin',
						'src' => 'git@github.com/foobar/bat',
						'revision' => 'zzz',
					],
					[
						'name' => 'my-plugin',
						'src' => 'git@github.com/foobar/baz',
						'revision' => '456789',
					],
				],
			]);

			$whippetLock->addDependency('plugins', 'my-plugin', 'git@github.com/foobar/baz', '123abc');
			expect($whippetLock->getDependencies('plugins'))->toEqual([
				[
					'name' => 'my-other-plugin',
					'src' => 'git@github.com/foobar/bat',
					'revision' => 'zzz',
				],
				[
					'name' => 'my-plugin',
					'src' => 'git@github.com/foobar/baz',
					'revision' => '123abc',
				],
			]);
		});
	});

	describe('->saveToPath()', function () {
		it('saves the data to the path', function () {
			$data = [
				'foo' => 'bar',
			];

			$whippetLock = new \Dxw\Whippet\Files\WhippetLock($data);

			$whippetLock->saveToPath($this->dir.'/my-whippet.lock');

			expect(file_exists($this->dir.'/my-whippet.lock'))->toBe(true);
			expect(json_decode(file_get_contents($this->dir.'/my-whippet.lock'), true))->toEqual($data);
		});

		it('saves the data with pretty printing and unescaped slashes', function () {
			$data = [
				'foo' => '/',
			];

			$whippetLock = new \Dxw\Whippet\Files\WhippetLock($data);

			$whippetLock->saveToPath($this->dir.'/my-whippet.lock');

			expect(file_exists($this->dir.'/my-whippet.lock'))->toBe(true);
			$expected = "{\n    \"foo\": \"/\"\n}\n";
			expect(file_get_contents($this->dir.'/my-whippet.lock'))->toEqual($expected);
		});
	});
});
