<?php

class Plugin extends RubbishThorClone {
  use manifest_io;
  use whippet_helpers;

  public function commands() {
    $this->command('install', 'Deploys the current set of plugins into your project');
    $this->command('upgrade PLUGIN', 'Upgrades PLUGIN to the most recent available version, or to the version specified in your Plugin file.');
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
            die();
          }
        }

        // Make sure repo is up to date.
        echo "[Checking {$dir}] ";
        if(!$git->checkout($plugin->revision)) {
          die();
        }

        $git->checkout($git->current_commit());
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

      //
      //  1. Compare the lockfile to the manifest. Delete any plugins that have been removed.
      //

      // Make sure every entry in the lockfile also appears in the manifest
      $plugins_to_delete = array_keys((Array)$this->plugins_locked->plugins);

      foreach($this->plugins_locked->plugins as $lock_dir => $lock_plugin) {
        foreach($this->plugins_manifest as $manifest_dir => $manifest_plugin) {
          if($lock_dir == $manifest_dir) {
            unset($plugins_to_delete[array_search($lock_dir, $plugins_to_delete)]);
          }
        }
      }

      // Delete the ones that don't
      foreach($plugins_to_delete as $dir) {
        echo "[Removing {$dir}]\n";
        $git = new Git("{$this->plugin_dir}/{$dir}");
        $git->delete_repo();

        // Remove from ignores
        $ignore_file = "{$this->project_dir}/.gitignore";
        $ignores = $git->get_ignores($ignore_file);

        foreach($plugins_to_delete as $dir) {
          $plugin_dir = "/wp-content/plugins/{$dir}\n";

          if(($index = array_search($plugin_dir, $ignores)) !== false) {
            unset($ignores[$index]);
          }
        }

        $git->save_ignores($ignores, $ignore_file);

        // Remove from the lockfile
        unset($this->plugins_locked->plugins->$dir);
      }


      //
      // 2. Check that the installed plugins are on the lockfile commit. Checkout the correct commit if not.
      //

      foreach($this->plugins_locked->plugins as $dir => $plugin) {
        $git = new Git("{$this->plugin_dir}/{$dir}");

        if(!$git->is_repo()) {
          // The repo has gone missing. Let's add it back.
          echo "[Adding {$dir}] ";
          $git->clone_repo($plugin->repository);
        }

        if($this->plugins_manifest->$dir->revision == $plugin->revision) {
          echo "[Checking {$dir}] ";
          $git->checkout($plugin->commit);
        }
        else {
          echo "[Updating {$dir}] ";
         $git->checkout($this->plugins_manifest->$dir->revision);
        }
      }


      //
      // 3. Compare the lockfile to the manifest. Clone any plugins that have been added.
      //

      // Make sure every entry in the lockfile also appears in the manifest
      $plugins_to_clone = array_keys((Array)$this->plugins_manifest);

      foreach($this->plugins_manifest as $manifest_dir => $manifest_plugin) {
        foreach($this->plugins_locked->plugins as $lock_dir => $lock_plugin) {
          if($lock_dir == $manifest_dir) {
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
            die();
          }
        }

        // Make sure repo is up to date.
        if(!$git->checkout($plugin->revision)) {
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

    $ignore_file = "{$this->project_dir}/.gitignore";
    $ignores = $git->get_ignores($ignore_file);

    foreach($this->plugins_locked->plugins as $dir => $plugin) {
      $plugin_dir = "/wp-content/plugins/{$dir}\n";

      if(array_search($plugin_dir, $ignores) === false) {
        $ignores[] = $plugin_dir;
      }
    }

    $git->save_ignores($ignores, $ignore_file);
  }

  /*
   * Checks the named plugin against the remote to see if the remote is on
   * a newer commit, and checks out the newer commit if so.
   */
  public function upgrade($upgrade_plugin) {
    $this->whippet_init();
    $this->load_plugins_manifest();
    $this->load_plugins_lock();


    //
    //  1. Find the plugin we're going to update.
    //  2. Check it out
    //  3. Update the lockfile

    foreach($this->plugins_manifest as $dir => $plugin) {
      // Find the specified revision.
      if($dir == $upgrade_plugin) {
        $git = new Git("{$this->plugin_dir}/{$dir}");

        echo "[Checking {$dir}] ";
        $git->fetch();

        $git->checkout($git->remote_revision_commit($plugin->revision));

        break;
      }
    }

    $this->update_plugins_lock();
  }
};
