<?php

namespace Dxw\Whippet;

class Factory
{
    public function newInstance()
    {
        $args = func_get_args();
        $className = $args[0];
        $constructorArgs = array_slice($args, 1);

        $class = new \ReflectionClass($className);
        return $class->newInstanceArgs($constructorArgs);
    }
}
