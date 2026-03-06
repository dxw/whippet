<?php

describe(\Dxw\Whippet\Files\WhippetJson::class, function () {
	describe('->getDependencies()', function () {
		it('returns plugins when they exist', function () {
			$whippetJson = new \Dxw\Whippet\Files\WhippetJson([
				'plugins' => [
					['name' => 'advanced-custom-fields'],
				],
			]);
			expect($whippetJson->getDependencies('plugins'))->toEqual([
				['name' => 'advanced-custom-fields'],
			]);
		});

		it('returns an empty array when no dependencies exist', function () {
			$whippetJson = new \Dxw\Whippet\Files\WhippetJson([]);
			expect($whippetJson->getDependencies('plugins'))->toEqual([]);
		});
	});

	describe('->getSources()', function () {
		it('returns sources', function () {
			$whippetJson = new \Dxw\Whippet\Files\WhippetJson([
				'src' => [
					'plugins' => 'git@github.com:dxw-wordpress-plugins/',
				],
			]);
			expect($whippetJson->getSources())->toEqual([
				'plugins' => 'git@github.com:dxw-wordpress-plugins/',
			]);
		});
	});

	describe('->getDependency()', function () {
		it('returns an empty array when no match is found', function () {
			$whippetJson = new \Dxw\Whippet\Files\WhippetJson([
				'plugins' => [
					[
						'name' => 'advanced-custom-fields',
						'ref' => 'foobar',
					],
				],
			]);
			expect($whippetJson->getDependency('plugins', 'twitget'))->toEqual([]);
		});

		it('returns the dependency when a match is found', function () {
			$whippetJson = new \Dxw\Whippet\Files\WhippetJson([
				'plugins' => [
					[
						'name' => 'twitget',
						'ref' => 'foobar',
					],
				],
			]);
			expect($whippetJson->getDependency('plugins', 'twitget'))->toEqual(['name' => 'twitget', 'ref' => 'foobar']);
		});
	});
});
