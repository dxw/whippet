<?php

namespace Dxw\Whippet\Services;

class JSONDecoderTest extends \PHPUnit_Framework_TestCase
{
    public function testValidJson()
    {
        $result = (new JSONDecoder)->decode('{"foo":"bar", "baz":[1, true]}');
        $this->assertFalse($result->isErr());
        $this->assertEquals(['foo' => 'bar', 'baz' => [1, true]], $result->unwrap());
    }

    public function testNull()
    {
        $result = (new JSONDecoder)->decode(null);
        $this->assertTrue($result->isErr());
        $this->assertEquals('Empty JSON', $result->getErr());
    }

    public function testEmpty()
    {
        $result = (new JSONDecoder)->decode('');
        $this->assertTrue($result->isErr());
        $this->assertEquals('Empty JSON', $result->getErr());
    }

    public function testInvalid()
    {
        $result = (new JSONDecoder)->decode('{foo}');
        $this->assertTrue($result->isErr());
        $this->assertEquals('Invalid JSON: Syntax error', $result->getErr());
    }

    public function testWrongQuotes()
    {
        $result = (new JSONDecoder)->decode("{'foo':'bar'}");
        $this->assertTrue($result->isErr());
        $this->assertEquals('Invalid JSON: Syntax error', $result->getErr());
    }
}
