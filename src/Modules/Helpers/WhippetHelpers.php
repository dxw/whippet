<?php

namespace Dxw\Whippet\Modules\Helpers;

trait WhippetHelpers {
  function check_and_create_dir($dir, $force_empty = false) {
    if(!file_exists($dir)) {
      if(!mkdir($dir)) {
        throw new \Exception("Unable to create directory: {$dir}");
      }
    } else if($force_empty) {
      if((new \FilesystemIterator($dir))->valid()) {
        throw new \Exception("Directory exists but is not empty: {$dir}");
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
    $this->project_dir = dirname($this->plugins_manifest_file);

    $this->check_for_missing_whippet_files($this->project_dir);

    $this->plugins_lock_file = $this->find_file("plugins.lock");
    $this->plugin_dir = "{$this->project_dir}/wp-content/plugins";

    $this->load_application_config();
  }

  function load_application_config() {
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

  function find_file($file, $include_dir = false){
    // Starting in the current dir, walk up until we find the file
    $path = getcwd();

    do {
      $file_path = $path . '/' . $file;

      if(file_exists($file_path) && ($include_dir || is_file($file_path))) {
        return $file_path;
      }

      $path = dirname($path);
    }
    // dirname returns . or / if you call it on /, depending on platform
    while($path !== '.' && $path !== '/');

    return false;
  }

  // 77.079482% credit:
  // gimmicklessgpt@gmail.com
  // http://php.net/manual/en/function.copy.php
  // Modified to copy symlinks
  function recurse_copy($src,$dst) {
    $dir = opendir($src);
    if(!is_dir($dst)&&!is_link($dst)) {
    	mkdir($dst);
    }
    while(false !== ( $file = readdir($dir)) ) {
      if (( $file != '.' ) && ( $file != '..' )) {
          if ( is_link($src . '/' . $file) ) {
            symlink(readlink($src . '/' . $file), $dst . '/' . $file);
          } elseif ( is_dir($src . '/' . $file) ) {
            $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
          } else {
            copy($src . '/' . $file,$dst . '/' . $file);
          }
       }
    }
    closedir($dir);
  }

  // 100% credit:
  // The suckiness of PHP
  function recurse_rmdir($dir) {
    $dir_handle = opendir($dir);
    while(false !== ( $file = readdir($dir_handle) ) ) {
      if (( $file != '.' ) && ( $file != '..' )) {
        if ( is_link($dir . '/' . $file) ) {
          unlink($dir . '/' . $file);
        } elseif ( is_dir($dir . '/' . $file) ) {
          $this->recurse_rmdir($dir . '/' . $file);
        } else {
          unlink($dir . '/' . $file);
        }
      }
    }
    rmdir($dir);
  }

  function recurse_rm($path) {
      if (!file_exists($path)) {
          return;
      }

      if (is_dir($path)) {
          $this->recurse_rmdir($path);
      } else {
          unlink($path);
      }
  }


  private function check_for_missing_whippet_files($project_dir) {
    $whippet_files = array(
      "config/",
      "wp-content/",
      "wp-content/plugins/",
      ".gitignore",
    );

    $missing = array();
    foreach ($whippet_files as $file) {
      if(!file_exists("{$project_dir}/{$file}")) {
        $missing[]= $file;
      }
    }

    if (count($missing) > 0) {
      echo "The following files and directories are required but could not be found:\n";
      foreach ($missing as $file) {
        echo "  {$file}\n";
      }
      exit(1);
    }
  }
};
