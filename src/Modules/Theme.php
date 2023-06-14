<?php

namespace Dxw\Whippet\Modules;

class Theme extends \RubbishThorClone
{
	use Helpers\WhippetHelpers;

	public function commands()
	{
		$this->command('grunt *arguments', 'Runs the specified grunt command in the context of your theme');/*, function($option_parser) {
			$option_parser->addRule('t|theme::', "Specify theme. Default: the theme directory you're in");
		});*/
	}

	/*
	* Commands
	*/

	/*
	* Runs the specified Grunt commands with the necessary arguments to make everything work with NPM and grunt in /vendor
	*/
	public function grunt($args)
	{
		$grunt_commands = $args;

		// Look for the theme base
		if (!isset($this->options->theme)) {
			if (!$vendor = $this->find_file('vendor', true)) {
				echo "Unable to find the theme's vendor directory\n";
				exit(1);
			}

			if (!isset($this->options)) {
				$this->options = new \stdClass();
			}

			$this->options->theme = dirname($vendor);
		} else {
			if (!file_exists($this->options->theme)) {
				echo "Specified theme directory not found\n";
				exit(1);
			}
		}

		// Sanity checks on contents of vendor
		if (!file_exists("{$this->options->theme}/vendor/Gruntfile.js") || !file_exists("{$this->options->theme}/vendor/package.json")) {
			echo "Found a theme at {$this->options->theme}, but its vendor directory doesn't look valid (no package.json or Gruntfile.js)\n";
			exit(1);
		}

		$this->options->theme = realpath($this->options->theme);

		system("grunt --base {$this->options->theme}/vendor --gruntfile {$this->options->theme}/vendor/Gruntfile.js {$grunt_commands}\n");
	}
};
