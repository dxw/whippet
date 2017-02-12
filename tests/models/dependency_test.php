<?php

namespace Dxw\Whippet;

class DependencyTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $dep = new Models\Dependency([ 'name' => 'my_dependency' ], 'plugins', 'https://dep_repo.com/');

        $this->assertEquals('my_dependency', $dep->name());
    }

    public function testType()
    {
        $dep = new Models\Dependency([], 'plugins', 'https://dep_repo.com/');

        $this->assertEquals('plugins', $dep->type());
    }

    public function testRef()
    {
        $dep = new Models\Dependency([ 'ref' => 'my_branch' ], 'plugins', 'https://dep_repo.com/');

        $this->assertEquals('my_branch', $dep->ref());
    }

    public function testRefDefault()
    {
        $dep = new Models\Dependency([], 'plugins', 'https://dep_repo.com/');

        $this->assertEquals('master', $dep->ref());
    }

    public function testSrc()
    {
        $dep = new Models\Dependency([ 'src' => 'https://other_repo.com/', 'name' => 'my_dependency' ], 'plugins', 'https://dep_repo.com/');

        $this->assertEquals('https://other_repo.com/', $dep->src());
    }

    public function testSrcDefault()
    {
        $dep = new Models\Dependency(['name' => 'my_dependency'], 'plugins', 'https://dep_repo.com/');

        $this->assertEquals('https://dep_repo.com/my_dependency', $dep->src());
    }

    public function testSrcNoDefault()
    {
        $dep = new Models\Dependency(['name' => 'my_dependency'], 'plugins', null);

        $this->assertEquals(null, $dep->src());
    }
}
