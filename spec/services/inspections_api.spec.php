<?php

use Kahlan\Plugin\Double;

describe(\Dxw\Whippet\Services\InspectionsApi::class, function () {
	beforeEach(function () {
		$this->jsonApi = Double::instance(['extends' => \Dxw\Whippet\Services\JsonApi::class, 'args' => [Double::instance(['extends' => \Dxw\Whippet\Services\BaseApi::class])]]);
		$this->api = new \Dxw\Whippet\Services\InspectionsApi('https://advisories.dxw.com', '/wp-json/v1/inspections/', $this->jsonApi);
	});

	describe('->getInspections()', function () {
		it('calls the API with the correct URL', function () {
			allow($this->jsonApi)->toReceive('get')
				->with('https://advisories.dxw.com/wp-json/v1/inspections/my-plugin')
				->andReturn(\Result\Result::ok([]));

			$result = $this->api->getInspections('my-plugin');
			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toEqual([]);
		});

		it('returns an empty array when no inspections are found', function () {
			allow($this->jsonApi)->toReceive('get')->andReturn(\Result\Result::ok([]));

			$result = $this->api->getInspections('my-plugin');

			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toEqual([]);
		});

		it('returns a list of Inspection objects when inspections are found', function () {
			$response = [
				[
					'name' => 'Slack',
					'slug' => 'slack',
					'date' => '2016-02-29T17:54:15+00:00',
					'versions' => '1.3.5',
					'url' => 'https://advisories.dxw.com/plugins/slack2/',
					'result' => 'Use with caution'
				],
				[
					'name' => 'Slack',
					'slug' => 'slack',
					'date' => '2015-06-17T23:00:12+00:00', // Fixed invalid hour for date_create
					'versions' => '1.1.3',
					'url' => 'https://advisories.dxw.com/plugins/slack/',
					'result' => 'No issues found'
				]
			];

			allow($this->jsonApi)->toReceive('get')->andReturn(\Result\Result::ok($response));

			$result = $this->api->getInspections('my-plugin');

			expect($result->isErr())->toBe(false);

			$result_body = $result->unwrap();
			expect(count($result_body))->toEqual(2);

			$inspection = array_shift($result_body);
			expect($inspection->date)->toEqual(date_create('2016-02-29T17:54:15+00:00'));
			expect($inspection->versions)->toEqual('1.3.5');
			expect($inspection->result)->toEqual('Use with caution');
			expect($inspection->url)->toEqual('https://advisories.dxw.com/plugins/slack2/');
		});

		it('returns an error when the JSON response is missing fields', function () {
			$response = [
				[
					'name' => 'Slack',
					'slug' => 'slack',
					'date' => '2016-02-29T17:54:15+00:00',
					'versions' => '1.3.5',
					'url' => 'https://advisories.dxw.com/plugins/slack2/',
					'result' => 'Use with caution'
				],
				[
					'name' => 'Slack',
					'slug' => 'slack',
					'date' => '2015-06-17T23:00:12+00:00',
					'versions' => '1.1.3',
					'url' => 'https://advisories.dxw.com/plugins/slack/',
					// missing result
				]
			];

			allow($this->jsonApi)->toReceive('get')->andReturn(\Result\Result::ok($response));

			$result = $this->api->getInspections('my-plugin');

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual("Couldn't extract inspections from JSON response");
		});

		it('bubbles up API errors', function () {
			allow($this->jsonApi)->toReceive('get')
				->andReturn(\Result\Result::err('A failure happened'));

			$result = $this->api->getInspections('my-plugin');

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual('A failure happened');
		});
	});
});
