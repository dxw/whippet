<?php

class Gitignore {
  function __construct($repo_path) {
    $this->ignore_file = "{$repo_path}/.gitignore";

    if(!file_exists("{$this->ignore_file}")) {
      echo "Couldn't find a .gitignore file. Aborting.../n";
      exit(1);
    }
  }

  function get_ignores() {
    return file($this->ignore_file);
  }

  function save_ignores($ignores) {
    return file_put_contents($this->ignore_file, $ignores);
  }
}