<?php

namespace Dxw\Whippet\Modules;

class Release {
  use Helpers\WhippetHelpers;
  use Helpers\ManifestIo;

  public $number = 0;
  public $time = 0;

  function __construct($releases_dir, $message, $number) {
    $this->whippet_init();
    $this->load_plugins_lock();

    $git = new \Dxw\Whippet\Git\Git($this->project_dir);

    $this->number = $number;
    $this->time = date('r');
    $this->deployed_commit = $git->current_commit();
    $this->release_dir = "{$releases_dir}/{$this->deployed_commit}";
  }

  function create(&$force) {
    //
    // Does this commit have a release directory already? If so, do nothing
    //

    if(!$force && file_exists($this->release_dir)) {
      return false;
    }

    // there's no point in forcing a non-existant release
    if($force && !file_exists($this->release_dir)) {
      $force = false;
    }

    // Got plugins.lock?
    if(!$this->plugins_lock_file || !file_exists($this->plugins_lock_file)) {
      echo "Couldn't find plugins.lock in the project directory. (Did you run whippet plugins install?)\n";
      die(1);
    }


    //
    // If we're here, we must deploy
    //


    //    1. Clone WP
    //    2. Delete wp-content etc
    //    3. Make sure wp-content is up to date
    //    4. Copy our wp-content, omitting gitfoo
    //    5. ?? Theme/plugin build steps ?? (Makefile-esque thing?)
    //    6. Symlink required files from shared dir

    // Assuming we're not forcing, create a new directory for this release, or use only an empty existing dir
    if(!$force) {
      $this->check_and_create_dir($this->release_dir, true);
    }
    else {
      $this->release_dir = dirname($this->release_dir ) . "/forced_release_tmp_" . sha1(microtime());
    }

    // Clone WP and remove things we don't want
    $wp = new \Dxw\Whippet\Git\Git($this->release_dir);
    $wp->clone_repo($this->application_config->wordpress->repository);
    $wp->checkout($this->application_config->wordpress->revision);

    foreach(['wp-content', '.git', 'readme.html', 'wp-config-sample.php'] as $delete) {
      if(is_dir("{$this->release_dir}/$delete")) {
        $this->recurse_rmdir("{$this->release_dir}/$delete");
      } else {
        unlink("{$this->release_dir}/$delete");
      }
    }

    // Make sure wp-content is up to date
    $plugin = new Plugin();
    $plugin->install();

    // Copy over wp-content
    $this->recurse_copy("{$this->project_dir}/wp-content","{$this->release_dir}/wp-content");


    //
    // Remove unwanted gitfoo
    //

    foreach($this->plugins_locked as $dir => $plugin) {
      foreach(['.git', '.gitmodules', '.gitignore'] as $delete) {
        if(is_dir("{$this->release_dir}/wp-content/plugins/$dir/{$delete}")) {
          $this->recurse_rmdir("{$this->release_dir}/wp-content/plugins/$dir/{$delete}");
        } elseif(file_exists("{$this->release_dir}/wp-content/plugins/$dir/{$delete}")) {
          unlink("{$this->release_dir}/wp-content/plugins/$dir/{$delete}");
        }
      }
    }

    //
    // Copy public assets
    //
    if(is_dir("{$this->project_dir}/public")) {
	  $this->recurse_copy("{$this->project_dir}/public","{$this->release_dir}");
	}


    //
    // TODO: theme and plugin build steps
    //


    // Symlinkery
    symlink(realpath("{$this->release_dir}/../../shared/wp-config.php"),"{$this->release_dir}/wp-config.php");
    symlink(realpath("{$this->release_dir}/../../shared/uploads"),"{$this->release_dir}/wp-content/uploads");

    // FIN
  }
};
