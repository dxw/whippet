<?php

class Json_Api_Test extends \PHPUnit\Framework\TestCase
{
	public function tearDown(): void
	{
		\Mockery::close();
	}

	public function testCallsApi()
	{
		$base_api = $this->fakeBaseApi();
		$base_api->shouldReceive('get')
			->once()
			->with('http://apisite.com/api/endpoint')
			->andReturn(\Result\Result::ok('[]'));

		$api = new \Dxw\Whippet\Services\JsonApi($base_api);
		$result = $api->get('http://apisite.com/api/endpoint');
		$this->assertFalse($result->isErr());
		$this->assertEquals([], $result->unwrap());
	}

	public function testEmptyResponse()
	{
		$base_api = $this->fakeBaseApi();
		$base_api->shouldReceive('get')->andReturn(\Result\Result::ok('[]'));

		$api = new \Dxw\Whippet\Services\JsonApi($base_api);
		$result = $api->get('http://apisite.com/api/endpoint');

		$this->assertFalse($result->isErr());
		$this->assertEquals([], $result->unwrap());
	}

	public function testWithInspections()
	{
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

		$base_api = $this->fakeBaseApi();
		$base_api->shouldReceive('get')->andReturn(\Result\Result::ok($response_body));

		$api = new \Dxw\Whippet\Services\JsonApi($base_api);
		$result = $api->get('https://advisories.dxw.com/api/endpoint');

		$this->assertFalse($result->isErr());
		$this->assertEquals($expected_result, $result->unwrap());
	}

	public function testInvalidJSON()
	{
		$base_api = $this->fakeBaseApi();
		$base_api->shouldReceive('get')
				 ->andReturn(\Result\Result::ok('<html><body>a webpage</body></html>'));

		$api = new \Dxw\Whippet\Services\JsonApi($base_api);
		$result = $api->get('http://apisite.com/api/endpoint');

		$this->assertTrue($result->isErr());
		$this->assertEquals('Failed to parse response body as JSON when requesting http://apisite.com/api/endpoint', $result->getErr());
	}

	public function testApiError()
	{
		$base_api = $this->fakeBaseApi();
		$base_api->shouldReceive('get')
				 ->andReturn(\Result\Result::err('A failure happened'));

		$api = new \Dxw\Whippet\Services\JsonApi($base_api);
		$result = $api->get('http://apisite.com/api/endpoint');

		$this->assertTrue($result->isErr());
		$this->assertEquals('A failure happened', $result->getErr());
	}

	private function fakeBaseApi()
	{
		return \Mockery::mock('\\Dxw\\Whippet\\Services\\BaseApi');
	}
}
