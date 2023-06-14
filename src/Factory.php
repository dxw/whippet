<?php

namespace Dxw\Whippet;

class Factory
{
	public function newInstance()
	{
		$args = func_get_args();
		$className = array_shift($args);

		$class = new \ReflectionClass($className);

		return $class->newInstanceArgs($args);
	}

	public function callStatic()
	{
		$args = func_get_args();
		$className = array_shift($args);
		$methodName = array_shift($args);

		return call_user_func_array([$className, $methodName], $args);
	}
}
