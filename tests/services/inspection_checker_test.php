<?php

class Inspection_Checker_Test extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

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
        $api->shouldReceive('get_inspections')
            ->once()
            ->with('my-plugin')
            ->andReturn(\Result\Result::ok([]));

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
        $api->shouldReceive('get_inspections')
            ->andReturn(\Result\Result::ok([]));

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
        $api->shouldReceive('get_inspections')
            ->andReturn(\Result\Result::ok([$inspection_ok, $inspection_caution]));

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

    private function fakeInspectionsApi()
    {
        return \Mockery::mock('\\Dxw\\Whippet\\Services\\InspectionsApi');
    }

    # test double
    private function fakeInspection($date, $result, $url)
    {
        return (object) [
            'date' => $date,
            'result' => $result,
            'url' => $url,
        ];
    }
}
