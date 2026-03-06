<?php

use Kahlan\Plugin\Double;

describe(\Dxw\Whippet\Services\InspectionChecker::class, function () {
	beforeEach(function () {
		$this->jsonApi = Double::instance(['extends' => \Dxw\Whippet\Services\JsonApi::class, 'args' => [Double::instance(['extends' => \Dxw\Whippet\Services\BaseApi::class])]]);
		$this->inspectionsApi = Double::instance(['extends' => \Dxw\Whippet\Services\InspectionsApi::class, 'args' => ['', '', $this->jsonApi]]);
		$this->checker = new \Dxw\Whippet\Services\InspectionChecker($this->inspectionsApi);
	});

	describe('->check()', function () {
		it('returns an empty string for themes', function () {
			$my_theme = [
				'name' => 'my-theme',
				'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
				'revision' => '27ba906',
			];
			$result = $this->checker->check('themes', $my_theme);

			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toEqual('');
		});

		it('calls the API for plugins', function () {
			allow($this->inspectionsApi)->toReceive('getInspections')->with('my-plugin')->andReturn(\Result\Result::ok([]));

			$my_plugin = [
				'name' => 'my-plugin',
				'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
				'revision' => '123456',
			];
			$result = $this->checker->check('plugins', $my_plugin);

			expect($result->isErr())->toBe(false);
		});

		it('generates a warning message when no inspections are found', function () {
			allow($this->inspectionsApi)->toReceive('getInspections')->andReturn(\Result\Result::ok([]));

			$my_plugin = [
				'name' => 'my-plugin',
				'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
				'revision' => '123456',
			];
			$result = $this->checker->check('plugins', $my_plugin);

			expect($result->isErr())->toBe(false);
			$warning_msg = <<<'EOT'
#############################################
#                                           #
#  WARNING: No inspections for this plugin  #
#                                           #
#############################################
EOT;
			expect($result->unwrap())->toEqual($warning_msg);
		});

		it('generates a formatted message when inspections are found', function () {
			$inspection_ok = (object) [
				'date' => date_create('2015-05-01'),
				'versions' => '2.3.4',
				'result' => 'No issues found',
				'url' => 'https://advisories.dxw.com/plugins/another_plugin/',
			];
			$inspection_caution = (object) [
				'date' => date_create('2016-01-23'),
				'versions' => '3.0.0',
				'result' => 'Use with caution',
				'url' => 'https://advisories.dxw.com/plugins/another_plugin2/',
			];

			allow($this->inspectionsApi)->toReceive('getInspections')->andReturn(\Result\Result::ok([$inspection_ok, $inspection_caution]));

			$my_plugin = [
				'name' => 'my-plugin',
				'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
				'revision' => '123456',
			];
			$result = $this->checker->check('plugins', $my_plugin);

			expect($result->isErr())->toBe(false);
			$expected_message = "Inspections for this plugin:\n* 01/05/2015 - 2.3.4 - No issues found - https://advisories.dxw.com/plugins/another_plugin/\n* 23/01/2016 - 3.0.0 - Use with caution - https://advisories.dxw.com/plugins/another_plugin2/";
			expect($result->unwrap())->toEqual($expected_message);
		});

		it('returns an error for unknown types', function () {
			$result = $this->checker->check('hedgehogs', []);

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual("Unknown type 'hedgehogs'");
		});

		it('returns an error if the API call fails', function () {
			allow($this->inspectionsApi)->toReceive('getInspections')->andReturn(\Result\Result::err('Something went wrong'));

			$my_plugin = [
				'name' => 'my-plugin',
				'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
				'revision' => '123456',
			];
			$result = $this->checker->check('plugins', $my_plugin);

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual("Error fetching plugin inspections from API: 'Something went wrong'");
		});
	});
});
