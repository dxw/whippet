<?php

namespace Dxw\Whippet\Dependencies;

class DependencyTypes
{
	public const PLUGINS = 'plugins';
	public const THEMES = 'themes';
	public const MU_PLUGINS = 'mu-plugins';


	public static function getDependencyTypes()
	{

		return [DependencyTypes::THEMES, DependencyTypes:: MU_PLUGINS, DependencyTypes::PLUGINS];
	}
}
