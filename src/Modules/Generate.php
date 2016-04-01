<?php

namespace Dxw\Whippet\Modules;

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
    echo "Available generators:\n\n";
    foreach($this->get_generators() as $generator => $file) {
      echo "  $generator\n";
    }
  }

  function get_generators() {
    $generators = array();

    foreach(new \DirectoryIterator($this->generators_dir) as $file) {
      if($file->isDot()) continue;

      if($file->isDir()) {
        $generator_file = $this->generators_dir . "/" . $file->getFilename() . "/generate.php";

        if(file_exists($generator_file)) {
          $generators[ucfirst($file->getFilename())] = $generator_file;
        }
      }
    }

    return $generators;
  }
};
