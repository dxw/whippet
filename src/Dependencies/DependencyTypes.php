<?php

namespace Dxw\Whippet\Dependencies;

class DependencyTypes
{
	public const PLUGINS = 'plugins';
	public const THEMES = 'themes';


	public static function getDependencyTypes()
	{

		return [DependencyTypes::THEMES, DependencyTypes::PLUGINS];
	}
}
