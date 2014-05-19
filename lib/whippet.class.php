<?php

require "rubbish_thor_clone/rubbish_thor_clone.class.php";
require dirname(__FILE__) . "/git/git.class.php";

require dirname(__FILE__) . "/modules/helpers/manifest_io.trait.php";


require "modules/plugin.class.php";
//require "modules/wordpress.class.php";

class Whippet extends RubbishThorClone {
  public function commands() {
    $this->command('plugin PLUGIN_COMMAND', '');
    //$this->command('wordpress WORDPRESS_COMMAND', '');
  }

  public function plugin($plugin_command) {
    $plugins = new Plugin;
    $plugins->start(array_slice($this->argv, 1));
  }

  // public function wordpress($plugin_command) {
  //   $wordpress = new WordPress;
  //   $wordpress->start(array_slice($this->argv, 1));
  // }
};
