<?php

class Generate {
  function __construct() {
    $this->generators_dir = WHIPPET_ROOT . '/generators';
  }

  function start($thing, $options) {
    if($thing) {
      $this->generate($thing, $options);
    }
    else {
      if(isset($options->list)) {
        $this->list_generators();
      }
    }
  }

  function generate($thing, $options) {
    $generator_file = "{$this->generators_dir}/{$thing}/generate.php";

    if(!file_exists($generator_file)) {
      echo "Could not find a generator for {$thing}\n";
      exit(1);
    }

    require $generator_file;
    $generator_class = ucfirst($thing) . "Generator";

    return (new $generator_class($options))->generate();
  }

  function list_generators() {
    foreach($this->get_generators() as $generator) {
      // Require file, instantiate generator, get description?
    }
  }

  function get_generators() {
    return array();
  }
};