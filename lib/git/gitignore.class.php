<?php

class Gitignore {
  function __construct($repo_path) {
    $this->ignore_file = "{$repo_path}/.gitignore";
  }

  function get_ignores() {
    return file($this->ignore_file);
  }

  function save_ignores($ignores) {
    return file_put_contents($this->ignore_file, $ignores);
  }
}