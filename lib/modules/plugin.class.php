<?php

class Plugin extends RubbishThorClone {
  use manifest_io;
  use whippet_helpers;

  public function commands() {
    $this->command('install', 'Deploys the current set of plugins into your project');
    $this->command('upgrade [PLUGIN]', 'Upgrades PLUGIN to the most recent available version, or to the version specified in your Plugin file.');
  }

  /*
   * Commands
   */

  /*
   * Adds new plugins that are missing, removes old plugins that have been removed, and
   * checks that plugins are on the revision referred to in the Plugins file.
   *
   * This command will not change an installed commit unless the revision has changed. It
   * just makes sure that what's in the project is what's in the file.
   */
  public function install() {
    $this->whippet_init();
    $this->load_plugins_manifest();
    $this->load_plugins_lock();

    if (count(get_object_vars($this->plugins_manifest)) == 0) {
      echo "The plugin manifest file is empty\n";
    }

    //
    // If there is no lock file:
    //
    //  1. Install everything from the manifest
    //  2. Update the lockfile
    //

    if(!$this->plugins_lock_file) {

      foreach($this->plugins_manifest as $dir => $plugin) {
        $git = new Git("{$this->plugin_dir}/{$dir}");

        // Is the repo there already?
        if(!$git->is_repo()) {
          echo "[Adding {$dir}] ";
          // We don't have the repo. Clone it.
          if(!$git->clone_repo($plugin->repository)) {
            echo "Aborting...\n";
            die();
          }
        }

        // Make sure repo is up to date.
        echo "[Checking {$dir}] ";
        if(!$git->checkout($plugin->revision)) {
          echo "Aborting...\n";
          die();
        }

        $git->checkout($git->current_commit());
        if(!$git->submodule_update()) {
          echo "Aborting...\n";
          die();
        }
      }
    }
    else {


      //
      // If there is a lock file:
      //
      //  1. Compare the lockfile to the manifest. Delete any plugins that have been removed.
      //  2. Check that the installed plugins are on the lockfile commit. Checkout the correct commit if not.
      //  3. Compare the lockfile to the manifest. Clone any plugins that have been added.
      //  4. Update the lockfile with the new list of commits.
      //  5. Delete any plugin dirs that are not in .gitmodules or plugins manifest

      //
      //  1. Compare the lockfile to the manifest. Delete any plugins that have been removed.
      //

      // Make sure every entry in the lockfile also appears in the manifest
      $plugins_to_delete = array_keys((Array)$this->plugins_locked);

      foreach($this->plugins_locked as $lock_dir => $lock_plugin) {
        foreach($this->plugins_manifest as $manifest_dir => $manifest_plugin) {
          if($lock_dir == $manifest_dir) {
            unset($plugins_to_delete[array_search($lock_dir, $plugins_to_delete)]);
          }
        }
      }

      // Delete the ones that don't:
      $gitignore = new Gitignore($this->project_dir);
      $ignores = $gitignore->get_ignores();

      foreach($plugins_to_delete as $dir) {
        echo "[Removing {$dir}]\n";
        $git = new Git("{$this->plugin_dir}/{$dir}");
        $git->delete_repo();

        // remove from ignores:
        $plugin_dir = "/wp-content/plugins/{$dir}\n";

        if(($index = array_search($plugin_dir, $ignores)) !== false) {
          unset($ignores[$index]);
        }

        // Remove from the lockfile
        unset($this->plugins_locked->$dir);
      }

      $gitignore->save_ignores($ignores);


      //
      // 2. Check that the installed plugins are on the lockfile commit. Checkout the correct commit if not.
      //

      foreach($this->plugins_locked as $dir => $plugin) {
        $git = new Git("{$this->plugin_dir}/{$dir}");

        if(!$git->is_repo()) {
          // The repo has gone missing. Let's add it back.
          echo "[Adding {$dir}] ";
          $git->clone_repo($plugin->repository);
        }

        if($this->plugins_manifest->$dir->repository != $plugin->repository) {
          // The remote has changed. Zap the plugin and add it again.
          $git->delete_repo();

          // The repo should be re-added below when we add new plugins
          continue;
        }

        // Check out a new revision, or if no new revision, check the existing one out again (in case of naughty changes)
        if($this->plugins_manifest->$dir->revision == $plugin->revision) {
          echo "[Checking {$dir}] ";
          $git->checkout($plugin->commit);
        }
        else {
          echo "[Updating {$dir}] ";
          $git->checkout($this->plugins_manifest->$dir->revision);
        }

        if(!$git->submodule_update()) {
          echo "Aborting...\n";
          die();
        }
      }


      //
      // 3. Compare the lockfile to the manifest. Clone any plugins that have been added.
      //

      // Make sure every entry in the lockfile also appears in the manifest
      $plugins_to_clone = array_keys((Array)$this->plugins_manifest);

      foreach($this->plugins_manifest as $manifest_dir => $manifest_plugin) {
        foreach($this->plugins_locked as $lock_dir => $lock_plugin) {
          if($lock_dir == $manifest_dir && $manifest_plugin->repository == $lock_plugin->repository) {
            unset($plugins_to_clone[array_search($manifest_dir, $plugins_to_clone)]);
          }
        }
      }

      foreach($plugins_to_clone as $dir) {
        $plugin = $this->plugins_manifest->$dir;

        echo "[Adding {$dir}] ";

        $git = new Git("{$this->plugin_dir}/{$dir}");

        // Is the repo there already?
        if(!$git->is_repo()) {
          // We don't have the repo. Clone it.
          if(!$git->clone_repo($plugin->repository)) {
            echo "Aborting...\n";
            die();
          }
        }

        // Make sure repo is up to date.
        if(!$git->checkout($plugin->revision)) {
          echo "Aborting...\n";
          die();
        }

        if(!$git->submodule_update()) {
          echo "Aborting...\n";
          die();
        }
      }
    }

    //
    // Update the lockfile
    //

    $this->update_plugins_lock();


    //
    // Make sure that Whippet-managed plugins are gitignored
    //
    $gitignore = new Gitignore($this->project_dir);
    $ignores = $gitignore->get_ignores();

    foreach($this->plugins_locked as $dir => $plugin) {
      $plugin_dir = "/wp-content/plugins/{$dir}\n";

      if(array_search($plugin_dir, $ignores) === false) {
        $ignores[] = $plugin_dir;
      }
    }

    $gitignore->save_ignores($ignores);
    
    //
    //  Remove plugins that are not present in either .gitmodules or plugins manifest
    //
    
    $plugin_dir_files = scandir($this->plugin_dir);
    $plugin_manifest_dirs = array_keys((Array)$this->plugins_manifest);
    
    $git = new Git("{$this->project_dir}");
    $submodules = $git->submodule_status();
    
    foreach($plugin_dir_files as $file) {
    	if(is_dir("{$this->project_dir}/wp-content/plugins/${file}") && substr($file,0,1) != '.') {
    		if(!isset($submodules["wp-content/plugins/${file}"]) && !isset(array_flip($plugin_manifest_dirs)[$file])) {
    			echo "[Removing ${file}] Plugins must either be whippet managed, or a Git submodule" . PHP_EOL;
    			system("rm -rf {$this->project_dir}/wp-content/plugins/${file}");
    		}
    	}
    }

    echo "Completed successfully\n";
  }

  /*
   * Checks the named plugin against the remote to see if the remote is on
   * a newer commit, and checks out the newer commit if so.
   */
  public function upgrade($upgrade_plugin = '') {
    $this->whippet_init();
    $this->load_plugins_manifest();
    $this->load_plugins_lock();

    //
    //  1. Find the plugin we're going to update.
    //  2. Check it out
    //  3. Update the lockfile
    //

    foreach($this->plugins_manifest as $dir => $plugin) {

      // Upgrade the plugin if:
      //  - It is the plugin they asked for
      //  - They didn't specify a plugin, and this plugin is in the manifest.
      if($dir == $upgrade_plugin || ($upgrade_plugin == '' && isset($this->plugins_manifest->$dir))) {
        $git = new Git("{$this->plugin_dir}/{$dir}");

        // Find the specified revision.
        echo "[Checking {$dir}] ";
        $git->fetch();

        // Check it out
        if(!$git->checkout($git->remote_revision_commit($plugin->revision))) {
          die();
        }

        if(!$git->submodule_update()) {
          die();
        }

        // If we were upgrading a specific plugin, bail now
        if($upgrade_plugin != '') {
          break;
        }
      }
    }

    $this->update_plugins_lock();
  }
};
