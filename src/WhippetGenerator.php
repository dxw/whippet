<?php

namespace Dxw\Whippet;

abstract class WhippetGenerator
{
	use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

	public function __construct($options)
	{
		//
		// This should not be called. You should declare your own constructor
		// which takes an $options stdobj containing the data your generator
		// requires.
		//

		trigger_error('Generators must declare a constructor', E_USER_FATAL);
		die();
	}

	abstract public function generate();
};
