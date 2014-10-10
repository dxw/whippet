<?php

date_default_timezone_set("UTC");


require "rubbish_thor_clone/rubbish_thor_clone.class.php";
require WHIPPET_ROOT . "/lib/git/git.class.php";
require WHIPPET_ROOT . "/lib/git/gitignore.class.php";

require WHIPPET_ROOT . "/lib/modules/helpers/manifest_io.trait.php";
require WHIPPET_ROOT . "/lib/modules/helpers/whippet_helpers.trait.php";

require WHIPPET_ROOT . "/lib/modules/plugin.class.php";
require WHIPPET_ROOT . "/lib/modules/theme.class.php";
require WHIPPET_ROOT . "/lib/modules/deploy.class.php";
require WHIPPET_ROOT . "/lib/modules/generate.class.php";
require WHIPPET_ROOT . "/lib/modules/db.class.php";

class Whippet extends RubbishThorClone {
  public function commands() {
    $this->command('plugins PLUGIN_COMMAND', '');
    $this->command('theme THEME_COMMAND', '');

    $this->command('deploy DIR', "Generates a working WordPress installation in DIR, based on the current contents of your app's repository", function($option_parser) {
      $option_parser->addRule('f|force', "Force Whippet to deploy, even if a release already exists for this commit");
      $option_parser->addRule('k|keep::', "Tells Whippet how many old release directories to keep. Default: 3");
    });

    $this->command('generate [THING]', 'Generates a thing', function($option_parser) {
      $option_parser->addRule('l|list', "Lists available generators");
      $option_parser->addRule('d|directory::', "Override the generator's default creation directory with this one");
    });


    $this->command('init [PATH]', "Creates a new Whippet application at PATH. NB: this is a shortcut for whippet generate -d PATH whippet.");

    $this->command('migrate OLDPATH NEWPATH', "Examines an existing wp-content directory and attempts to create an identical Whippet application.");
    $this->command('db DB_COMMAND', '');
  }

  public function plugins($plugin_command) {
    (new Plugin)->start(array_slice($this->argv, 1));
  }

  public function theme($plugin_command) {
    (new Theme)->start(array_slice($this->argv, 1));
  }

  public function deploy($dir) {
    if(!isset($this->options->keep)) {
      $this->options->keep = 3;
    }

    (new Deploy($dir))->deploy(isset($this->options->force), $this->options->keep);
  }

  public function init($path = false) {
    if($path) {
      $this->options = new stdClass();
      $this->options->directory = $path;
    }

    (new Generate())->start("whippet", $this->options);
  }

  public function generate($thing = false) {
    (new Generate())->start($thing, $this->options);
  }

  // TODO: This should just be a generator like any other, but it's currently hard to do one
  //       command with several different combinations of required arguments. So just a separate
  //       command for now.
  public function migrate($old, $new) {
    $this->options = new stdClass();
    $this->options->old = $old;
    $this->options->new = $new;

    (new Generate())->start("migration", $this->options);
  }

  public function db() {
    (new Db)->start(array_slice($this->argv, 1));
  }
};
