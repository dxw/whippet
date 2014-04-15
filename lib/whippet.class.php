<?php

require "rubbish_thor_clone/rubbish_thor_clone.class.php";

class Plugin extends RubbishThorClone {
  public function commands() {
    $this->command('add REPO REVISION', 'Adds the plugin in REPO at REVISION. REVISION needs to be a valid branch, commit or tag in REPO.', function($parser) {
      $parser->addRule('d|dir:', 'Set the path the plugin is added at (defaults to repo name)');
    });
    $this->command('remove REPO', 'Removes the plugin in REPO from the project.');
    $this->command('update', 'Clones all the plugins in your project into the plugins directory.');
  }

  private function init(){
    if(!$plugins_json = $this->find_plugins_json()) {
      echo "Unable to find plugins.json";
      exit(1);
    }

    $this->plugins_json = $this->find_plugins_json();
    $this->project_dir = dirname($this->plugins_json);
    $this->plugins = $this->load_plugins($this->plugins_json);
  }

  private function save_json()
  {
    file_put_contents($this->plugins_json . ".new", json_encode($this->plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

  public function add($repo, $revision){
    if(isset($this->options->dir)) {
      $directory = $this->options->dir;
    }
    else {
      $repo_bits = explode('/', $repo);
      $directory = array_pop($repo_bits);
    }

    $new_plugin = new stdClass();

    $new_plugin->repository = $repo;
    $new_plugin->revision = $revision;

    $this->init();

    $this->plugins->plugins->$directory = $new_plugin;

    $this->save_json();
  }

  public function remove($repo){
    $this->init();

    foreach($this->plugins->plugins as $dir => $plugin){
      if($plugin->repository == $repo) {
        unset($this->plugins->plugins->$dir);
        $this->save_json();

        return;
      }
    }

    echo "Unable to find {$repo} in this project\n";
  }

  public function update(){
    echo "Update";
  }

  protected function load_plugins($plugins_json){
    $plugins = json_decode(file_get_contents($plugins_json));

    // TODO: handle invalid json properly
    // http://www.php.net/manual/en/function.json-last-error.php
    if(!is_object($plugins)) {
      echo "Unable to parse plugins.json";
      exit(1);
    }

    return $plugins;
  }

  protected function find_plugins_json(){
    // Starting in the current dir, walk up until we find a plugins.json

    $path = getcwd();

    do {
      $plugins_json = $path . '/plugins.json';
      if(file_exists($plugins_json)) {
        return $plugins_json;
      }
    }
    while($path = dirname($path) != '.');

    return false;
  }
};

class Whippet extends RubbishThorClone {
  public function commands() {
    $this->command('plugin PLUGIN_COMMAND', '');
  }

  public function plugin($plugin_command) {

    $plugins = new Plugin;
    $plugins->start(array_slice($this->argv, 1));
  }
};
