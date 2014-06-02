<?php

date_default_timezone_set("UTC");


require "rubbish_thor_clone/rubbish_thor_clone.class.php";
require WHIPPET_ROOT . "/lib/git/git.class.php";

require WHIPPET_ROOT . "/lib/modules/helpers/manifest_io.trait.php";
require WHIPPET_ROOT . "/lib/modules/helpers/whippet_helpers.trait.php";


require WHIPPET_ROOT . "/lib/modules/plugin.class.php";
require WHIPPET_ROOT . "/lib/modules/deploy.class.php";
require WHIPPET_ROOT . "/lib/modules/generate.class.php";

class Whippet extends RubbishThorClone {
  public function commands() {
    $this->command('plugin PLUGIN_COMMAND', '');

    $this->command('deploy DIR', "Generates a working WordPress installation in DIR, based on the current contents of your app's repository", function($option_parser) {
      $option_parser->addRule('f|force', "Force Whippet to deploy, even if a release already exists for this commit");
    });

    $this->command('generate THING', 'Generates a thing', function($option_parser) {
      $option_parser->addRule('l|list', "Lists available generators");
      $option_parser->addRule('d|directory::', "Override the generator's default creation directory with this one");
    });
  }

  public function plugin($plugin_command) {
    $plugins = new Plugin;
    $plugins->start(array_slice($this->argv, 1));
  }

  public function deploy($dir) {
    $deploy = new Deploy($dir);
    $deploy->deploy(isset($this->options->force));
  }

  public function generate($thing = false) {
    $generate = new Generate($thing);

    $generate->start($thing, $this->options);
  }
};
