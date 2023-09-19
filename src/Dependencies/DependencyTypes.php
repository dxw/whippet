<?php

namespace Dxw\Whippet\Dependencies;

/**
 * Encapsulate dependency types.
 *
 * Note that language packs are managed differently to other WordPress
 * dependencies, and so we have some methods here that deal specifically with
 * them. This is mainly to avoid future refactoring, for example in PHP 8
 * this class is likely to become an enumeration.
 */
class DependencyTypes
{
	public const PLUGINS = 'plugins';
	public const THEMES = 'themes';
	public const LANGUAGES = 'languages';


	public static function getDependencyTypes()
	{
		return [DependencyTypes::THEMES, DependencyTypes::PLUGINS, DependencyTypes::LANGUAGES];
	}

	public static function getThemeAndPluginTypes()
	{
		return [DependencyTypes::THEMES, DependencyTypes::PLUGINS];
	}

	public static function isLanguageType($type)
	{
		return $type === DependencyTypes::LANGUAGES;
	}

	public static function isNotLanguageType($type)
	{
		return $type !== DependencyTypes::LANGUAGES;
	}
}
