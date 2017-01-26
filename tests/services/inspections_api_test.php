<?php

class Inspections_Api_Test extends PHPUnit_Framework_TestCase
{
    public function testCallsApi()
    {
        $json_api = $this->fakeJsonApi();
        $json_api->expects($this->once())
            ->method('get')
            ->with('https://security.dxw.com/wp-json/wp/v2/plugins?slug=my-plugin')
            ->willReturn(\Result\Result::ok([]));

        $api = new \Dxw\Whippet\Services\InspectionsApi('https://security.dxw.com', $json_api);
        $api->get_inspections('my-plugin');
    }

    public function testNoInspections()
    {
        $json_api = $this->fakeJsonApi();
        $json_api->method('get')->willReturn(\Result\Result::ok([]));

        $api = new \Dxw\Whippet\Services\InspectionsApi('https://security.dxw.com', $json_api);
        $result = $api->get_inspections('my-plugin');

        $this->assertFalse($result->isErr());
        $this->assertEquals([], $result->unwrap());
    }

    public function testWithInspections()
    {
        $response = [
            [
                "id" => 2644,
                "date" => "2016-07-13T17:44:23",
                "date_gmt" => "2016-07-13T17:44:23",
                "guid" => [
                    "rendered" => "https://security.dxw.com/?post_type=plugins&#038;p=2644"
                ],
                "modified" => "2016-08-08T18:26:16",
                "modified_gmt" => "2016-08-08T18:26:16",
                "slug" => "advanced-custom-fields-table-field",
                "type" => "plugins",
                "link" => "http://localhost:8000/plugins/advanced-custom-fields-table-field/",
                "title" => [
                    "rendered" => "Advanced Custom Fields: Table Field"
                ],
                "author" => 5,
                "_links" => [
                    "self" => [
                        [
                            "href" => "http://localhost:8000/wp-json/wp/v2/plugins/2644"
                        ]
                    ],
                    "collection" => [
                        [
                            "href" => "http://localhost:8000/wp-json/wp/v2/plugins"
                        ]
                    ],
                    "about" => [
                        [
                            "href" => "http://localhost:8000/wp-json/wp/v2/types/plugins"
                        ]
                    ],
                    "author" => [
                        [
                            "embeddable" => true,
                            "href" => "http://localhost:8000/wp-json/wp/v2/users/5"
                        ]
                    ],
                    "version-history" => [
                        [
                            "href" => "http://localhost:8000/wp-json/wp/v2/plugins/2644/revisions"
                        ]
                    ],
                    "https://api.w.org/attachment" => [
                        [
                            "href" => "http://localhost:8000/wp-json/wp/v2/media?parent=2644"
                        ]
                    ]
                ]
            ]
        ];

        $json_api = $this->fakeJsonApi();
        $json_api->method('get')->willReturn(\Result\Result::ok($response));

        $api = new \Dxw\Whippet\Services\InspectionsApi('https://security.dxw.com', $json_api);
        $result = $api->get_inspections('my-plugin');

        $this->assertFalse($result->isErr());

        $result_body = $result->unwrap();
        $this->assertEquals(1, count($result_body));

        $inspection = array_shift($result_body);
        $this->assertEquals(date_create('2016-07-13T17:44:23'), $inspection->date);
        $this->assertEquals('Unknown', $inspection->result);
        $this->assertEquals('http://localhost:8000/plugins/advanced-custom-fields-table-field/', $inspection->url);
    }

    public function testApiError()
    {
        $json_api = $this->fakeJsonApi();
        $json_api->method('get')
                 ->willReturn(\Result\Result::err('A failure happened'));

        $api = new \Dxw\Whippet\Services\InspectionsApi('https://security.dxw.com', $json_api);
        $result = $api->get_inspections('my-plugin');

        $this->assertTrue($result->isErr());
        $this->assertEquals('A failure happened', $result->getErr());
    }

    private function fakeJsonApi()
    {
        $stub = $this->createMock('\\Dxw\\Whippet\\Services\\JsonApi');
        return $stub;
    }
}
