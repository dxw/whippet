<?php

class Inspection_Checker_Test extends PHPUnit_Framework_TestCase
{
    public function testTheme()
    {
        $my_theme = [
            'name' => 'my-theme',
            'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
            'revision' => '27ba906',
        ];
        $checker = new \Dxw\Whippet\Services\InspectionChecker($this->fakeInspectionsApi());
        $result = $checker->check('themes', $my_theme);

        $this->assertFalse($result->isErr());
        $this->assertEquals('', $result->unwrap());
    }

    public function testPluginCallsAPI()
    {
        $api = $this->fakeInspectionsApi();
        $api->expects($this->once())
            ->method('get_inspections')
            ->with($this->equalTo('my-plugin'))
            ->willReturn(\Result\Result::ok([]));

        $my_plugin = [
            'name' => 'my-plugin',
            'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
            'revision' => '123456',
        ];
        $checker = new \Dxw\Whippet\Services\InspectionChecker($api);
        $checker->check('plugins', $my_plugin);
    }

    public function testPluginWithNoInspectionsGeneratesMessage()
    {
        $api = $this->fakeInspectionsApi();
        $api->expects($this->once())
            ->method('get_inspections')
            ->willReturn(\Result\Result::ok([]));

        $my_plugin = [
            'name' => 'my-plugin',
            'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
            'revision' => '123456',
        ];
        $checker = new \Dxw\Whippet\Services\InspectionChecker($api);
        $result = $checker->check('plugins', $my_plugin);

        $this->assertFalse($result->isErr());
        $this->assertEquals('[WARNING] No inspections for this plugin', $result->unwrap());
    }

    public function testPluginWithInspectionsGeneratesMessage()
    {
        $inspection_ok = $this->fakeInspection(date_create('2015-05-01'), 'No issues found', 'https://security.dxw.com/plugins/another_plugin/');
        $inspection_caution = $this->fakeInspection(date_create('2016-01-23'), 'Use with caution', 'https://security.dxw.com/plugins/another_plugin2/');

        $api = $this->fakeInspectionsApi();
        $api->expects($this->once())
            ->method('get_inspections')
            ->willReturn(\Result\Result::ok([$inspection_ok, $inspection_caution]));

        $my_plugin = [
            'name' => 'my-plugin',
            'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
            'revision' => '123456',
        ];
        $checker = new \Dxw\Whippet\Services\InspectionChecker($api);
        $result = $checker->check('plugins', $my_plugin);

        $this->assertFalse($result->isErr());
        $expected_message = "Inspections for this plugin:\n* 01/05/2015 - No issues found - https://security.dxw.com/plugins/another_plugin/\n* 23/01/2016 - Use with caution - https://security.dxw.com/plugins/another_plugin2/";
        $this->assertEquals($expected_message, $result->unwrap());
    }

    public function testUnknownType()
    {
        $checker = new \Dxw\Whippet\Services\InspectionChecker($this->fakeInspectionsApi());
        $result = $checker->check('hedgehogs', []);

        $this->assertTrue($result->isErr());
        $this->assertEquals("Unknown type 'hedgehogs'", $result->getErr());
    }

    private function fakeInspectionsApi()
    {
        $stub = $this->createMock('\\Dxw\\Whippet\\Services\\InspectionsApi');
        return $stub;
    }

    # test double
    private function fakeInspection($date, $result, $url)
    {
        $inspection = new StdClass;
        $inspection->date = $date;
        $inspection->result = $result;
        $inspection->url = $url;
        return $inspection;
    }
}
