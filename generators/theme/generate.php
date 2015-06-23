<?php

require WHIPPET_ROOT . "/generators/whippet_generator.class.php";

class ThemeGenerator extends WhippetGenerator {
  use whippet_helpers;

  function __construct($options) {
    $this->options = $options;

    if(isset($this->options->directory)) {
      $this->target_dir = $this->options->directory;
    }
    else {
      $this->target_dir = getcwd() . "/whippet-theme";
    }
  }

  function generate() {
    echo "Creating a new whippet theme in {$this->target_dir}\n";

    if(!file_exists($this->target_dir)) {
      mkdir($this->target_dir);
    }

    $tmpl_path = dirname(dirname(dirname(__FILE__))) . '/vendor/dxw/whippet-theme-template';

    // The . is necessary for this command to work in Linux
    system("cp -a " . "{$tmpl_path}/. {$this->target_dir}");

    // Delete the spurious .git file
    system("rm {$this->target_dir}/.git");
   }
};
