<?php

class WhippetGenerator { // extends Generator?
  use whippet_helpers;

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

    try {
      $this->check_and_create_dir($this->target_dir, true);
    }
    catch(Exception $e) {
      echo "Failed: " . $e->getMessage() . "\n";

      exit(1);
    }

    system("cp -r " . dirname(__FILE__) . "/template/* {$this->target_dir}");
   }
};