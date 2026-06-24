<?php

namespace Dxw\Whippet;

/**
 * @psalm-suppress UnusedClass
 */
class Whippet extends \RubbishThorClone
{
	public function commands()
	{
		$this->command('plugins PLUGIN_COMMAND', '');

		$this->command('dependencies SUBCOMMAND', 'Manage dependencies (themes, plugins)');
		$this->command('deps SUBCOMMAND', 'Alias for dependencies');
	}

	public function plugins()
	{
		(new Modules\Plugin())->start(array_slice($this->argv, 1));
	}

	public function init($path = false)
	{
		if ($path) {
			$this->options->directory = $path;
		}
	}

	public function dependencies()
	{
		(new Modules\Dependencies())->start(array_slice($this->argv, 1));
	}

	public function deps()
	{
		$this->dependencies();
	}
};
