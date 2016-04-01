<?php

class AppGenerator extends \Dxw\Whippet\WhippetGenerator {
  use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

  function __construct($options) {
    $this->options = $options;

    if(isset($this->options->directory)) {
      $this->target_dir = $this->options->directory;
    }
    else {
      $this->target_dir = getcwd() . "/whippet-app";
    }
  }

  function generate() {
    echo "Creating a new whippet application in {$this->target_dir}\n";

    if(!file_exists($this->target_dir)) {
      mkdir($this->target_dir);
    }

    // Make the target dir a git repo, if it isn't already
    if(!(new \Dxw\Whippet\Git\Git($this->target_dir))->is_repo()) {
      \Dxw\Whippet\Git\Git::init($this->target_dir);
    }

    $this->recurse_copy(dirname(__FILE__) . "/template",$this->target_dir);
   }
};
