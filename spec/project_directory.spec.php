<?php

use Kahlan\Plugin\Double;
use org\bovigo\vfs\vfsStream;

describe(\Dxw\Whippet\ProjectDirectory::class, function () {
	beforeEach(function () {
		$this->root = vfsStream::setup();
		$this->dir = $this->root->url();
	});

	describe('::find()', function () {
		context('when whippet.json is in the current directory', function () {
			it('returns the project directory', function () {
				mkdir($this->dir.'/wp-content/themes/my-theme', 0777, true);
				touch($this->dir.'/whippet.json');

				foreach ([
					$this->dir.'/wp-content/themes/my-theme',
					$this->dir.'/wp-content/themes',
					$this->dir.'/wp-content',
					$this->dir,
				] as $path) {
					$result = \Dxw\Whippet\ProjectDirectory::find($path);
					expect($result->isErr())->toBe(false);
					expect($result->unwrap())->toBeAnInstanceOf(\Dxw\Whippet\ProjectDirectory::class);
					expect($result->unwrap()->__toString())->toEqual($this->dir);
				}
			});
		});

		context('when whippet.json is in a parent directory', function () {
			it('returns the project directory', function () {
				mkdir($this->dir.'/projects/project1/wp-content/themes/my-theme', 0777, true);
				touch($this->dir.'/projects/project1/whippet.json');

				foreach ([
					$this->dir.'/projects/project1/wp-content/themes/my-theme',
					$this->dir.'/projects/project1/wp-content/themes',
					$this->dir.'/projects/project1/wp-content',
					$this->dir.'/projects/project1',
				] as $path) {
					$result = \Dxw\Whippet\ProjectDirectory::find($path);
					expect($result->isErr())->toBe(false);
					expect($result->unwrap())->toBeAnInstanceOf(\Dxw\Whippet\ProjectDirectory::class);
					expect($result->unwrap()->__toString())->toEqual($this->dir.'/projects/project1');
				}
			});
		});

		context('when whippet.json is not found', function () {
			it('returns an error', function () {
				mkdir($this->dir.'/projects/project1/wp-content/themes/my-theme', 0777, true);
				touch($this->dir.'/plugins');

				foreach ([
					$this->dir.'/projects/project1/wp-content/themes/my-theme',
					$this->dir.'/projects/project1/wp-content/themes',
					$this->dir.'/projects/project1/wp-content',
					$this->dir.'/projects/project1',
				] as $path) {
					$result = \Dxw\Whippet\ProjectDirectory::find($path);
					expect($result->isErr())->toBe(true);
					expect($result->getErr())->toEqual('whippet.json not found');
				}
			});
		});

		context('when it is in a plugins directory', function () {
			it('finds the project directory from the plugins directory', function () {
				mkdir($this->dir.'/wp-content/plugins/my-plugin', 0777, true);
				touch($this->dir.'/whippet.json');

				$result = \Dxw\Whippet\ProjectDirectory::find($this->dir.'/wp-content/plugins/my-plugin');
				expect($result->isErr())->toBe(false);
				expect($result->unwrap())->toBeAnInstanceOf(\Dxw\Whippet\ProjectDirectory::class);
				expect($result->unwrap()->__toString())->toEqual($this->dir);
			});
		});
	});
});
