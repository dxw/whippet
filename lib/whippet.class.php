<?php

require "lame_thor_clone.class.php";

class Whippet extends LameThorClone {

  public function commands() {
    $this->command('hello NAME', 'say hello to NAME');
  }

  public function hello($name) {
    echo "Hi, $name!\n";
  }
};