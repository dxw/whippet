<?php

use Kahlan\Plugin\Double;

describe(\Dxw\Whippet\Services\JsonApi::class, function () {
	beforeEach(function () {
		$this->baseApi = Double::instance(['extends' => \Dxw\Whippet\Services\BaseApi::class]);
		$this->api = new \Dxw\Whippet\Services\JsonApi($this->baseApi);
	});

	describe('->get()', function () {
		it('calls the base API with the correct URL and parses the JSON response', function () {
			allow($this->baseApi)->toReceive('get')
				->with('http://apisite.com/api/endpoint')
				->andReturn(\Result\Result::ok('[]'));

			$result = $this->api->get('http://apisite.com/api/endpoint');
			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toEqual([]);
		});

		it('returns an empty array for an empty JSON list response', function () {
			allow($this->baseApi)->toReceive('get')->andReturn(\Result\Result::ok('[]'));

			$result = $this->api->get('http://apisite.com/api/endpoint');

			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toEqual([]);
		});

		it('parses a complex JSON response correctly', function () {
			$response_body = '[{"id":2644,"date":"2016-07-13T17:44:23","date_gmt":"2016-07-13T17:44:23","guid":{"rendered":"https:\/\/advisories.dxw.com\/?post_type=plugins&#038;p=2644"},"modified":"2016-08-08T18:26:16","modified_gmt":"2016-08-08T18:26:16","slug":"advanced-custom-fields-table-field","type":"plugins","link":"https:\/\/advisories.dxw.com\/plugins\/advanced-custom-fields-table-field\/","title":{"rendered":"Advanced Custom Fields: Table Field"},"author":5,"_links":{"self":[{"href":"https:\/\/advisories.dxw.com\/wp-json\/wp\/v2\/plugins\/2644"}],"collection":[{"href":"https:\/\/advisories.dxw.com\/wp-json\/wp\/v2\/plugins"}],"about":[{"href":"https:\/\/advisories.dxw.com\/wp-json\/wp\/v2\/types\/plugins"}],"author":[{"embeddable":true,"href":"https:\/\/advisories.dxw.com\/wp-json\/wp\/v2\/users\/5"}],"version-history":[{"href":"https:\/\/advisories.dxw.com\/wp-json\/wp\/v2\/plugins\/2644\/revisions"}],"https:\/\/api.w.org\/attachment":[{"href":"https:\/\/advisories.dxw.com\/wp-json\/wp\/v2\/media?parent=2644"}]}}]';
			$expected_result = [
				[
					"id" => 2644,
					"date" => "2016-07-13T17:44:23",
					"date_gmt" => "2016-07-13T17:44:23",
					"guid" => [
						"rendered" => "https://advisories.dxw.com/?post_type=plugins&#038;p=2644"
					],
					"modified" => "2016-08-08T18:26:16",
					"modified_gmt" => "2016-08-08T18:26:16",
					"slug" => "advanced-custom-fields-table-field",
					"type" => "plugins",
					"link" => "https://advisories.dxw.com/plugins/advanced-custom-fields-table-field/",
					"title" => [
						"rendered" => "Advanced Custom Fields: Table Field"
					],
					"author" => 5,
					"_links" => [
						"self" => [
							[
								"href" => "https://advisories.dxw.com/wp-json/wp/v2/plugins/2644"
							]
						],
						"collection" => [
							[
								"href" => "https://advisories.dxw.com/wp-json/wp/v2/plugins"
							]
						],
						"about" => [
							[
								"href" => "https://advisories.dxw.com/wp-json/wp/v2/types/plugins"
							]
						],
						"author" => [
							[
								"embeddable" => true,
								"href" => "https://advisories.dxw.com/wp-json/wp/v2/users/5"
							]
						],
						"version-history" => [
							[
								"href" => "https://advisories.dxw.com/wp-json/wp/v2/plugins/2644/revisions"
							]
						],
						"https://api.w.org/attachment" => [
							[
								"href" => "https://advisories.dxw.com/wp-json/wp/v2/media?parent=2644"
							]
						]
					]
				]
			];

			allow($this->baseApi)->toReceive('get')->andReturn(\Result\Result::ok($response_body));

			$result = $this->api->get('https://advisories.dxw.com/api/endpoint');

			expect($result->isErr())->toBe(false);
			expect($result->unwrap())->toEqual($expected_result);
		});

		it('returns an error for invalid JSON', function () {
			allow($this->baseApi)->toReceive('get')
				->andReturn(\Result\Result::ok('<html><body>a webpage</body></html>'));

			$result = $this->api->get('http://apisite.com/api/endpoint');

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual('Failed to parse response body as JSON when requesting http://apisite.com/api/endpoint');
		});

		it('bubbles up base API errors', function () {
			allow($this->baseApi)->toReceive('get')
				->andReturn(\Result\Result::err('A failure happened'));

			$result = $this->api->get('http://apisite.com/api/endpoint');

			expect($result->isErr())->toBe(true);
			expect($result->getErr())->toEqual('A failure happened');
		});
	});
});
