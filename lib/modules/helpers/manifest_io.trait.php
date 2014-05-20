<?php

trait manifest_io {
  protected function load_plugins_manifest() {
    $plugins = parse_ini_file($this->plugins_manifest_file);

    if(!is_array($plugins)) {
      echo "Unable to parse Plugins file";
      exit(1);
    }

    // Got plugins - turn names to sources
    $source = '';
    $this->plugins_manifest = new stdClass();
    foreach($plugins as $plugin => $data) {
      if($plugin == 'source') {
        if(empty($data)) {
          echo "Source is empty. It should just specify a repo root:\n\n  source = 'git@git.dxw.net:wordpress-plugins/'\n\nWhippet will attempt to find a source for your plugins by appending the plugin name to this URL.";
          exit(1);
        }
        $source = $data;
        continue;
      }

      $repository = $revision = '';

      // Everything else should be a plugin
      // First see if there is data.
      if(!empty($data)) {
        // Format: LABEL[, REPO]
        if(strpos($data, ',') !== false) {
          list($revision, $repository) = explode(',', $data);
        }
        else {
          $revision = $data;
        }
      }

      if(empty($repository)) {
        $repository = "{$source}{$plugin}";
      }

      if (empty($revision)) {
        $revision = "master";
      }

      // We should now have repo and revision
      $this->plugins_manifest->$plugin = new stdClass();
      $this->plugins_manifest->$plugin->repository = $repository;
      $this->plugins_manifest->$plugin->revision = $revision;
    }
  }

  protected function load_plugins_lock(){
    if(!$this->plugins_lock_file) {
      return false;
    }

    $this->plugins_locked = json_decode(file_get_contents($this->plugins_lock_file));

    // TODO: handle invalid json properly
    // http://www.php.net/manual/en/function.json-last-error.php
    if(!is_object($this->plugins_locked)) {
      echo "Unable to parse plugins.lock";
      exit(1);
    }
  }

  private function update_plugins_lock()
  {
    if($this->plugins_lock_file) {
      $this->old_plugins_locked = $this->plugins_locked;
    }

    $this->plugins_lock_file = 'plugins.lock';
    $this->plugins_locked = new stdClass();
    $this->plugins_locked->plugins = new stdClass();

    foreach(scandir($this->plugin_dir) as $dir) {
      if($dir[0] == '.') {
        continue;
      }

      if(!isset($this->plugins_manifest->$dir)) {
        continue;
      }

      $git = new Git("{$this->plugin_dir}/{$dir}");

      if(!$commit = $git->current_commit())
      {
        echo "Unable to determine current commit; aborting\n";
        exit(1);
      }

      $this->plugins_locked->plugins->$dir              = new stdClass();
      $this->plugins_locked->plugins->$dir->repository  = $this->plugins_manifest->$dir->repository;
      $this->plugins_locked->plugins->$dir->revision    = $this->plugins_manifest->$dir->revision;
      $this->plugins_locked->plugins->$dir->commit      = $commit;
    }

    return file_put_contents($this->plugins_lock_file, json_encode($this->plugins_locked, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }
};