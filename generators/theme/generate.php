<?php

use Dxw\Whippet\Git\Git;

class ThemeGenerator extends \Dxw\Whippet\WhippetGenerator {
  use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

  protected $whippet_theme_repo = 'https://github.com/dxw/whippet-theme-template.git';

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

    (new Git($this->target_dir))->clone_repo($this->whippet_theme_repo);
    // Delete the spurious .git file
    chdir($this->target_dir);
    $this->recurse_rm('.git');

    $namespace = $this->get_namespace_from_target_dir();
    echo "Setting namespace to {$namespace}\n";
    exec(sprintf("find . -type f -exec sed -i '' -e 's/MyTheme/%s/g' {} \;", escapeshellarg($namespace)));
   }

  function get_namespace_from_target_dir()
  {
    $base_dir = basename($this->target_dir);
    $words = explode('-', $base_dir);
    return implode('', array_map('ucfirst', $words));
  }
};
