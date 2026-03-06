<?php

use Kahlan\Plugin\Double;
use Kahlan\Arg;
use org\bovigo\vfs\vfsStream;

describe(\Dxw\Whippet\Dependencies\Updater::class, function () {
	beforeEach(function () {
		$this->root = vfsStream::setup();
		$this->dir = $this->root->url();
		$this->factory = Double::instance(['extends' => \Dxw\Whippet\Factory::class]);
		$this->projectDirectory = Double::instance([
			'extends' => \Dxw\Whippet\ProjectDirectory::class,
			'args' => [$this->dir]
		]);
		$this->updater = new \Dxw\Whippet\Dependencies\Updater(
			$this->factory,
			$this->projectDirectory
		);
	});

	describe('->updateAll()', function () {
		it('updates all dependencies and returns an OK result', function () {
			$whippetJson = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetJson::class, 'args' => [[]]]);
			allow($whippetJson)->toReceive('getSources')->andReturn([
				'themes' => 'git@github.com:dxw-wordpress-themes/',
				'plugins' => 'git@github.com:dxw-wordpress-plugins/',
			]);
			allow($whippetJson)->toReceive('getDependencies')->with('themes')->andReturn([
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			]);
			allow($whippetJson)->toReceive('getDependencies')->with('plugins')->andReturn([
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			]);
			allow(\Dxw\Whippet\Files\WhippetJson::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetJson));

			file_put_contents($this->dir.'/whippet.json', 'foobar');

			$gitignore = Double::instance(['extends' => \Dxw\Whippet\Git\Gitignore::class, 'args' => [$this->dir]]);
			allow($gitignore)->toReceive('get_ignores')->andReturn([]);
			allow($gitignore)->toReceive('save_ignores');
			allow($this->factory)->toReceive('newInstance')->with(\Dxw\Whippet\Git\Gitignore::class, \Kahlan\Arg::toBe($this->dir))->andReturn($gitignore);

			$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
			allow($whippetLock)->toReceive('getDependencies')->andReturn([]);
			expect($whippetLock)->toReceive('addDependency')->with('themes', 'my-theme', 'git@github.com:dxw-wordpress-themes/my-theme', '27ba906');
			expect($whippetLock)->toReceive('addDependency')->with('plugins', 'my-plugin', 'git@github.com:dxw-wordpress-plugins/my-plugin', 'd961c3d');
			expect($whippetLock)->toReceive('setHash')->with(sha1('foobar'));
			expect($whippetLock)->toReceive('saveToPath')->with($this->dir.'/whippet.lock');
			allow($whippetLock)->toReceive('getDependencies')->andReturn([]);
			allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

			allow(\Dxw\Whippet\Git\Git::class)->toReceive('::ls_remote')->with('git@github.com:dxw-wordpress-themes/my-theme', 'v1.4')->andReturn(\Result\Result::ok('27ba906'));
			allow(\Dxw\Whippet\Git\Git::class)->toReceive('::ls_remote')->with('git@github.com:dxw-wordpress-plugins/my-plugin', 'v1.6')->andReturn(\Result\Result::ok('d961c3d'));

			expect(function () {
				$result = $this->updater->updateAll();
				expect($result->isErr())->toBe(false);
			})->toEcho("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n");
		});

		context('when there are errors', function () {
			it('bubbles up WhippetJson errors', function () {
				allow(\Dxw\Whippet\Files\WhippetJson::class)->toReceive('::fromFile')->andReturn(\Result\Result::err('a WhippetJson error'));

				$result = $this->updater->updateAll();

				expect($result->isErr())->toBe(true);
				expect($result->getErr())->toEqual('whippet.json: a WhippetJson error');
			});

			it('returns an error if sources are missing', function () {
				$whippetJson = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetJson::class, 'args' => [[]]]);
				allow($whippetJson)->toReceive('getSources')->andReturn([
					'plugins' => 'git@github.com:dxw-wordpress-plugins/',
				]);
				allow($whippetJson)->toReceive('getDependencies')->with('themes')->andReturn([
					[
						'name' => 'my-theme',
						'ref' => 'v1.4',
					],
				]);
				allow($whippetJson)->toReceive('getDependencies')->with('plugins')->andReturn([]);
				allow(\Dxw\Whippet\Files\WhippetJson::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetJson));

				file_put_contents($this->dir.'/whippet.json', 'foobar');

				$gitignore = Double::instance(['extends' => \Dxw\Whippet\Git\Gitignore::class, 'args' => [$this->dir]]);
				allow($gitignore)->toReceive('get_ignores')->andReturn([]);
				allow($this->factory)->toReceive('newInstance')->andReturn($gitignore);

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getDependencies')->andReturn([]);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				expect(function () {
					$result = $this->updater->updateAll();
					expect($result->isErr())->toBe(true);
					expect($result->getErr())->toEqual('missing sources');
				})->toEcho("[Updating themes/my-theme]\n");
			});
		});
	});

	describe('->updateSingle()', function () {
		it('updates a single dependency', function () {
			file_put_contents($this->dir.'/whippet.json', 'foobar');

			$whippetJson = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetJson::class, 'args' => [[]]]);
			allow($whippetJson)->toReceive('getSources')->andReturn([
				'plugins' => 'git@github.com:dxw-wordpress-plugins/',
			]);
			allow($whippetJson)->toReceive('getDependencies')->with('themes')->andReturn([]);
			allow($whippetJson)->toReceive('getDependencies')->with('plugins')->andReturn([
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			]);
			allow($whippetJson)->toReceive('getDependency')->with('plugins', 'my-plugin')->andReturn([
				'name' => 'my-plugin',
				'ref' => 'v1.6',
			]);
			allow(\Dxw\Whippet\Files\WhippetJson::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetJson));

			$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
			allow($whippetLock)->toReceive('getDependencies')->andReturn([]);
			expect($whippetLock)->toReceive('addDependency')->with('plugins', 'my-plugin', 'git@github.com:dxw-wordpress-plugins/my-plugin', 'd961c3d');
			allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

			allow(\Dxw\Whippet\Git\Git::class)->toReceive('::ls_remote')->andReturn(\Result\Result::ok('d961c3d'));

			$gitignore = Double::instance(['extends' => \Dxw\Whippet\Git\Gitignore::class, 'args' => [$this->dir]]);
			allow($gitignore)->toReceive('get_ignores')->andReturn([]);
			allow($gitignore)->toReceive('save_ignores');
			allow($this->factory)->toReceive('newInstance')->andReturn($gitignore);

			expect(function () {
				$result = $this->updater->updateSingle('plugins/my-plugin');
				expect($result->isErr())->toBe(false);
			})->toEcho("[Updating plugins/my-plugin]\n");
		});

		it('returns an error if no whippet.lock exists', function () {
			allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::err('file not found'));

			expect(function () {
				$result = $this->updater->updateSingle('plugins/twitget');
				expect($result->isErr())->toBe(true);
				expect($result->getErr())->toEqual('whippet.lock: file not found');
			})->toEcho("No whippet.lock file exists, you need to run `whippet deps update` to generate one before you can update a specific dependency. \n");
		});
	});
});
