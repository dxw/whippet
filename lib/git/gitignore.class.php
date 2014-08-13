<?php

class Gitignore {
  function __construct($repo_path) {
    $this->ignore_file = "{$repo_path}/.gitignore";
  }

  function get_ignores() {
    return $this->ensure_closing_newline(file($this->ignore_file));
  }

  function save_ignores($ignores) {
    return file_put_contents($this->ignore_file, $ignores);
  }

  private function ensure_closing_newline($ignores) {
    $index_of_last_line = count( $ignores ) - 1;
    $last_line = $ignores[$index_of_last_line];
    $last_character = substr($last_line, -1);

    if ($last_character != "\n") {
      $ignores[$index_of_last_line] =  $last_line . "\n";
    }
    return $ignores;
  }
}