<?php

require "rubbish_thor_clone/rubbish_thor_clone.class.php";
require dirname(__FILE__) . "/git/git.class.php";

require dirname(__FILE__) . "/modules/helpers/manifest_io.trait.php";


require "modules/plugin.class.php";

class Whippet extends RubbishThorClone {
  public function commands() {
    $this->command('plugin PLUGIN_COMMAND', '');
  }

  public function plugin($plugin_command) {
    $plugins = new Plugin;
    $plugins->start(array_slice($this->argv, 1));
  }
};
