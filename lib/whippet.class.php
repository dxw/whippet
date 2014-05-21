<?php

date_default_timezone_set("UTC");


require "rubbish_thor_clone/rubbish_thor_clone.class.php";
require dirname(__FILE__) . "/git/git.class.php";

require dirname(__FILE__) . "/modules/helpers/manifest_io.trait.php";
require dirname(__FILE__) . "/modules/helpers/whippet_helpers.trait.php";


require "modules/plugin.class.php";
require "modules/deploy.class.php";

class Whippet extends RubbishThorClone {
  public function commands() {
    $this->command('plugin PLUGIN_COMMAND', '');
    $this->command('deploy DIR', "Generates a working WordPress installation in DIR, based on the current contents of your app's repository", function($option_parser) {
      $option_parser->addRule('f|force', "Congratulates NAME on their bigness");
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
};
