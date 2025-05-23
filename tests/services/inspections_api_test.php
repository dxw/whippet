<?php

class Inspections_Api_Test extends \PHPUnit\Framework\TestCase
{
	public function tearDown(): void
	{
		\Mockery::close();
	}

	public function testCallsApi()
	{
		$json_api = $this->fakeJsonApi();
		$json_api->shouldReceive('get')
			->once()
			->with('https://advisories.dxw.com/wp-json/v1/inspections/my-plugin')
			->andReturn(\Result\Result::ok([]));

		$api = new \Dxw\Whippet\Services\InspectionsApi('https://advisories.dxw.com', '/wp-json/v1/inspections/', $json_api);
		$result = $api->getInspections('my-plugin');
		$this->assertFalse($result->isErr());
		$this->assertEquals([], $result->unwrap());
	}

	public function testNoInspections()
	{
		$json_api = $this->fakeJsonApi();
		$json_api->shouldReceive('get')->andReturn(\Result\Result::ok([]));

		$api = new \Dxw\Whippet\Services\InspectionsApi('https://advisories.dxw.com', '/wp-json/v1/inspections/', $json_api);
		$result = $api->getInspections('my-plugin');

		$this->assertFalse($result->isErr());
		$this->assertEquals([], $result->unwrap());
	}

	public function testWithInspections()
	{
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
				'date' => '2015-06-17T24:00:12+00:00',
				'versions' => '1.1.3',
				'url' => 'https://advisories.dxw.com/plugins/slack/',
				'result' => 'No issues found'
			]
		];

		$json_api = $this->fakeJsonApi();
		$json_api->shouldReceive('get')->andReturn(\Result\Result::ok($response));

		$api = new \Dxw\Whippet\Services\InspectionsApi('https://advisories.dxw.com', '/wp-json/v1/inspections/', $json_api);
		$result = $api->getInspections('my-plugin');

		$this->assertFalse($result->isErr());

		$result_body = $result->unwrap();
		$this->assertEquals(2, count($result_body));

		$inspection = array_shift($result_body);
		$this->assertEquals(date_create('2016-02-29T17:54:15'), $inspection->date);
		$this->assertEquals('1.3.5', $inspection->versions);
		$this->assertEquals('Use with caution', $inspection->result);
		$this->assertEquals('https://advisories.dxw.com/plugins/slack2/', $inspection->url);
	}

	public function testWithInspectionsWithMissingFields()
	{
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
				'date' => '2015-06-17T24:00:12+00:00',
				'versions' => '1.1.3',
				'url' => 'https://advisories.dxw.com/plugins/slack/',
				// missing result
			]
		];

		$json_api = $this->fakeJsonApi();
		$json_api->shouldReceive('get')->andReturn(\Result\Result::ok($response));

		$api = new \Dxw\Whippet\Services\InspectionsApi('https://advisories.dxw.com', '/wp-json/v1/inspections/', $json_api);
		$result = $api->getInspections('my-plugin');

		$this->assertTrue($result->isErr());

		$error_message = $result->getErr();
		$this->assertEquals("Couldn't extract inspections from JSON response", $error_message);
	}

	public function testApiError()
	{
		$json_api = $this->fakeJsonApi();
		$json_api->shouldReceive('get')
				 ->andReturn(\Result\Result::err('A failure happened'));

		$api = new \Dxw\Whippet\Services\InspectionsApi('https://advisories.dxw.com', '/wp-json/v1/inspections/', $json_api);
		$result = $api->getInspections('my-plugin');

		$this->assertTrue($result->isErr());
		$this->assertEquals('A failure happened', $result->getErr());
	}

	private function fakeJsonApi()
	{
		$stub = \Mockery::mock('\\Dxw\\Whippet\\Services\\JsonApi');
		return $stub;
	}
}
