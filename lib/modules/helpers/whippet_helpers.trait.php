<?php

trait whippet_helpers {
  function check_and_create_dir($dir, $force_empty = false) {
    if(!file_exists($dir)) {
      if(!mkdir($dir)) {
        throw new Exception("Unable to create directory: {$dir}");
      }
    } else if($force_empty) {
      if((new \FilesystemIterator($dir))->valid()) {
        throw new Exception("Directory exists but is not empty: {$dir}");
      }
    }
  }

  function whippet_init(){
    if(!$this->plugins_manifest_file = $this->find_file('plugins')) {
      if(!$this->plugins_manifest_file = $this->find_file('Plugins')) {
        echo "Unable to find plugins manifest file\n";
        exit(1);
      }
    }

    $this->plugins_lock_file = $this->find_file("plugins.lock");

    $this->project_dir = dirname($this->plugins_manifest_file);
    $this->plugin_dir = "{$this->project_dir}/wp-content/plugins";

    $this->load_application_config();
  }

  function load_application_config() {
    if(!file_exists("{$this->project_dir}/config/")) {
      echo "Couldn't find a /config directory. Is this definitely a whippet application?\n";
      exit(1);
    }

    $application_config_file = "{$this->project_dir}/config/application.json";

    if(file_exists($application_config_file)) {
      $this->application_config = json_decode(file_get_contents($application_config_file));

      if(!is_object($this->application_config)) {
        echo "Unable to parse application config";
        exit(1);
      }
    }
    else {
      $this->application_config = json_decode('
        {
          "wordpress": {
            "repository": "git@git.dxw.net:wordpress/snapshot",
            "revision": "master"
          }
        }
      ');

      if(file_put_contents($application_config_file, json_encode($this->application_config,  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        echo "A default application.json was created\n";
      }
      else {
        echo "No config/application.json was found, and no default could be created. Quitting.\n";
        exit(1);
      }
    }
  }

  function find_file($file){
    // Starting in the current dir, walk up until we find a plugins file
    $path = getcwd();

    do {
      $file_path = $path . '/' . $file;
      if(file_exists($file_path) && is_file($file_path)) {
        return $file_path;
      }
      $path = dirname($path);
    }
    // FIXME: Should this stop earlier?
    //   Bundler doesn't, but has a more robust ending condition:
    //     until !File.directory?(current) || current == previous
    //   https://github.com/bundler/bundler/blob/d61b1ac60227b82f451c0858f60558ecbc80ee54/lib/bundler/shared_helpers.rb
    while($path !== '/');

    return false;
  }
};