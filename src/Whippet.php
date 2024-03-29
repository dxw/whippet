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
		$this->command('theme THEME_COMMAND', '');

		$this->command('deploy DIR', "Generates a working WordPress installation in DIR, based on the current contents of your app's repository", function ($option_parser) {
			$option_parser->addRule('f|force', 'Force Whippet to deploy, even if a release already exists for this commit');
			$option_parser->addRule('k|keep::', 'Tells Whippet how many old release directories to keep. Default: 3');
			$option_parser->addRule('p|public::', 'Deploy public/ in a given directory, adjacent to the app');
		});

		$this->command('generate [THING]', 'Generates a thing', function ($option_parser) {
			$option_parser->addRule('l|list', 'Lists available generators');
			$option_parser->addRule('d|directory::', "Override the generator's default creation directory with this one");
			$option_parser->addRule('n|nogitignore', 'When generating a theme, do not generate the accompanying .gitignore file');
			$option_parser->addRule('r|repository::', 'When generating an app, override the default application.json WordPress repository with this one');
		});

		$this->command('init [PATH]', 'Creates a new Whippet application at PATH. NB: this is a shortcut for whippet generate -d PATH whippet.', function ($option_parser) {
			$option_parser->addRule('r|repository::', 'Override the default application.json WordPress repository with this one');
		});
		$this->command('dependencies SUBCOMMAND', 'Manage dependencies (themes, plugins)');
		$this->command('deps SUBCOMMAND', 'Alias for dependencies');
	}

	public function plugins()
	{
		(new Modules\Plugin())->start(array_slice($this->argv, 1));
	}

	public function theme()
	{
		(new Modules\Theme())->start(array_slice($this->argv, 1));
	}

	public function deploy($dir)
	{
		if (!isset($this->options->keep)) {
			$this->options->keep = 3;
		}

		if (!isset($this->options->public)) {
			$this->options->public = "";
		}

		(new Modules\Deploy($dir))->deploy(
			isset($this->options->force),
			$this->options->keep,
			$this->options->public
		);
	}

	public function init($path = false)
	{
		if ($path) {
			$this->options->directory = $path;
		}

		(new Modules\Generate())->start('app', $this->options);
	}

	public function generate($thing = false)
	{
		(new Modules\Generate())->start($thing, $this->options);
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
