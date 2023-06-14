<?php

use Kahlan\Plugin\Double;

describe(Dxw\Whippet\Dependencies\Describer::class, function () {
	beforeEach(function () {
		$this->factory = Double::instance(['extends' => '\Dxw\Whippet\Factory']);
		$this->projectDirectory = Double::instance([
			'extends' => 'Dxw\Whippet\ProjectDirectory',
			'magicMethods' => true
		]);
		$this->describer = new \Dxw\Whippet\Dependencies\Describer(
			$this->factory,
			$this->projectDirectory
		);
	});

	describe('->describe()', function () {
		context('whippet.lock file not loaded successfully', function () {
			it('returns an error result', function () {
				allow($this->factory)->toReceive('callStatic')->andReturn(\Result\Result::err('Error loading whippet.lock'));

				$result = $this->describer->describe();

				expect($result->isErr())->toEqual(true);
				expect($result->getErr())->toEqual('Error loading whippet.lock');
			});
		});

		context('Error getting the references for one of the git repos', function () {
			it('returns an error result', function () {
				$whippetLock = Double::instance([
					'extends' => '\Dxw\Whippet\Files\WhippetLock',
					'magicMethods' => true
				]);
				allow($whippetLock)->toReceive('getDependencies')->andReturn([
					[
						'name' => 'plugin-one',
						'src' => 'plugin-one-src',
						'revision' => 'commit-hash'
					]
				]);
				allow($this->factory)->toReceive('callStatic')->andReturn(\Result\Result::ok($whippetLock));
				$git = Double::instance([
					'extends' => '\Dxw\Whippet\Git\Git',
					'magicMethods' => true
				]);
				allow(\Dxw\Whippet\Git\Git::class)->toBe($git);
				allow($git)->toReceive('::tag_for_commit')->andReturn(\Result\Result::err('Error getting tag'));

				$result = $this->describer->describe();

				expect($result->isErr())->toEqual(true);
				expect($result->getErr())->toEqual('Error getting tag');
			});
		});

		it('outputs a JSON report and returns an OK result', function () {
			$whippetLock = Double::instance([
				'extends' => '\Dxw\Whippet\Files\WhippetLock',
				'magicMethods' => true
			]);
			allow($whippetLock)->toReceive('getDependencies')->andReturn([
				[
					'name' => 'theme-one',
					'src' => 'theme-one-src',
					'revision' => 'commit-hash'
				]
			], [
				[
					'name' => 'plugin-one',
					'src' => 'plugin-one-src',
					'revision' => 'commit-hash'
				],
			]);
			allow($this->factory)->toReceive('callStatic')->andReturn(\Result\Result::ok($whippetLock));
			$git = Double::instance([
				'extends' => '\Dxw\Whippet\Git\Git',
				'magicMethods' => true
			]);
			allow(\Dxw\Whippet\Git\Git::class)->toBe($git);
			allow($git)->toReceive('::tag_for_commit')->andReturn(\Result\Result::ok('v1.0.1'), \Result\Result::ok('v3.0'));

			ob_start();

			$result = $this->describer->describe();

			$output = ob_get_clean();

			expect(json_decode($output, null, 5, JSON_OBJECT_AS_ARRAY))->toEqual([
				'themes' => [
					'theme-one' => 'v1.0.1'
				],
				'plugins' => [
					'plugin-one' => 'v3.0'
				]
			]);
			expect($result->isErr())->toBe(false);
		});
	});
});
