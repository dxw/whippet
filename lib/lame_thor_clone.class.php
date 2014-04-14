<?php

require dirname(__FILE__) . "/optionparser/lib/OptionParser.php";

abstract class LameThorClone {
  private $executable;
  private $command;

  private $options;
  private $command_defs;
  private $option_parser;

  function __construct() {
    $this->option_parser = new OptionParser;
    $this->commands();
  }

  public abstract function commands();

  public function start($argv) {
    $this->executable = $argv[0];

    if(count($argv) > 1) {
      $this->command      = $argv[1];
      $this->command_def  = $this->command_defs[$this->command];
    }
    else {
      $this->usage();
      exit(1);
    }

    # Find a command with this name
    if(!isset($this->command_defs[$this->command])) {
      # No such command
      $this->usage();
      exit(1);
    }

    # Make sure we've got the method
    if(!method_exists($this, $this->command)) {
      echo "Error: no command handler\n";
      exit(1);
    }

    # Get the arguments and check there are enough
    $arguments = array_slice($argv, 2);

    if(count($arguments) != count($this->command_def['arguments'])) {
      echo "Error: wrong number of arguments; expected: {$this->command_def['definition']}\n";
      $this->usage();
      exit(1);
    }

    # TODO Add support for options
    #$this->option_parser->parse();

    call_user_func_array(array($this, $this->command), $arguments);
  }

  # TODO: nice alignment
  public function usage() {
    echo "Tasks:\n\n";

    foreach($this->command_defs as $command => $def) {
      echo "  {$this->executable} {$this->command} ";

      foreach($def['arguments'] as $argument) {
        echo "{$argument} ";
      }

      echo "    # {$def['description']}\n";
    }
  }

  protected function command($definition, $description) {
    $command_bits = explode(' ', $definition);

    $name = $command_bits[0];
    array_shift($command_bits);
    $arguments = $command_bits;

    $this->command_defs[$name] = array(
      'arguments'   => $arguments,
      'description' => $description,
      'definition'  => $definition,
    );
  }
};