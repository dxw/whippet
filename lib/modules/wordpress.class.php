<?php
class WordPress extends RubbishThorClone {
  use manifest_io;

  public function commands() {
    $this->command('install', 'Deploys the specified WordPress version');
    $this->command('upgrade', 'Upgrades WordPress to the most recent available commit, or to the version specified in your Plugin file.');
  }

  /*
   * Commands
   */

  /*
   * Installs the WordPress version specified in Plugins.
   */
  public function install() {
    $this->manifest_init();

    $wp_dir = "{$this->project_dir}/wordpress";

    $git = new Git($wp_dir);

    // Whatever we do here, we need to make a temporary copy of the wp-content
    // dir in order to stop git wrecking our application, so make sure we can
    // do that before carrying on.

    do {
      $tmp_wpcontent = $git->get_tmpdir($wp_dir);
    } while(file_exists($tmp_wpcontent));


    //
    // What's the WP revision we should use? Use the commit from the lockfile
    // if there is one, or the manifest revision if not.
    //

    if(!$this->plugins_lock_file || !isset($this->wordpress_locked) || $this->wordpress_locked->revision |= $this->wordpress_manifest->revision) {
      $wp_revision = $this->wordpress_manifest->revision;
      $wp_repository =  $this->wordpress_manifest->repository;
    }
    else {
      $wp_revision = $this->wordpress_locked->commit;
      $wp_repository =  $this->wordpress_locked->repository;
    }


    // Do we have wp already? If not, do a checkoutless clone and move .git into the wp dir
    // We can't just clone because our wp-content means the dir is non-empty
    if(!$git->is_repo()) {
      if(!$git->clone_no_checkout($wp_repository)) {
        exit(1);
      }

      $output = array();
      $return = 0;

      // Temporarily move our files out of the way, so the reset doesn't modify them
      exec("mv {$wp_dir}/wp-content {$tmp_wpcontent}", $output, $return);

      if($return) {
        echo "Unable to move app wp-content\n";
        exit(1);
      }

      // Hard reset the repo, to get the core files into the working tree
      if(!$git->hard_reset($wp_revision)) {
        // Undo the move
        exec("mv {$tmp_wpcontent} {$wp_dir}/wp-content", $output, $return);
        exit(1);
      }
    }
    // We already have WP, so we don't need to clone it
    else {
      // As before, do a temporary move to save our application's files
      exec("mv {$wp_dir}/wp-content {$tmp_wpcontent}", $output, $return);

      if($return) {
        echo "Unable to move app wp-content\n";
        exit(1);
      }

      // With them out of the way, check out the revision
      if(!$git->checkout($wp_revision)) {
        // Undo the move
        exec("mv {$tmp_wpcontent} {$wp_dir}/wp-content", $output, $return);
        exit(1);
      }
    }

    // Now we have the core files and our app in a temp directory. So get rid
    // of the core files we don't want, and move the directory back. Deleting
    // the distro wp-content from the index is useful because we can then use
    // git to detect changes to the core files

    // Check out the current commit
    $git->checkout($git->current_commit());

    //
    // Is there an upstream wp-content we need to remove?
    // There won't be if we've checked out a commit from
    // the lockfile, otherwise, there will - and we need to get rid of it.
    //
    // Also reset, so that we can check things out later with git moaning.
    //

    if(file_exists("{$wp_dir}/wp-content")) {
      if(!$git->rm("wp-content", true) || !$git->mixed_reset()) {
        echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
        exit(1);
      }

      // Ignore the app's wp-content
      file_put_contents("{$wp_dir}/.gitignore", "/wp-content\n");
    }

    // Don't think we can do this. Whippet adds the commit to the lockfile, and if it's
    // a local commit that never gets pushed, nothing will work anywhere except locally
    // // Add it to the commit (perhaps not really necessary, but never mind)
    // if(!$git->add(".gitignore")) {
    //   echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
    //   exit(1);
    // }

    // // Commit!
    // // NB: This repo will never be pushed anywhere, and if we change the revision
    // // we want, these commits will just get left behind. This is ok.
    // if(!$git->commit("Remove upstream wp-content and ignore app wp-content")) {
    //   echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
    //   exit(1);
    // }

    // All done. Move the app's wp-content back.
    exec("mv {$tmp_wpcontent} {$wp_dir}/wp-content", $output, $return);

    if($return) {
      echo "Unable to restore app wp-content\n";

      if(file_exists($tmp_wpcontent)) {
        echo "Whippet has left this in: {$tmp_wpcontent}\n";
      }

      exit(1);
    }

    $this->update_wordpress_lock();
  }


  /*
   * Checks WordPress against the remote to see if the remote is on
   * a newer commit, and checks out the newer commit if so.
   */
  public function upgrade() {
    $this->manifest_init();

    $wp_dir = "{$this->project_dir}/wordpress";

    $git = new Git($wp_dir);

    do {
      $tmp_wpcontent = $git->get_tmpdir($wp_dir);
    } while(file_exists($tmp_wpcontent));

    echo "[Checking] ";
    $git->fetch();

    exec("mv {$wp_dir}/wp-content {$tmp_wpcontent}", $output, $return);

    if($return) {
      echo "Unable to move app wp-content\n";
      exit(1);
    }

    $git->checkout($git->remote_revision_commit($this->wordpress_manifest->revision));

    if(file_exists("{$wp_dir}/wp-content")) {
      if(!$git->rm("wp-content", true) || !$git->mixed_reset()) {
        echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
        exit(1);
      }

      // Ignore the app's wp-content
      file_put_contents("{$wp_dir}/.gitignore", "/wp-content\n");
    }

    // All done. Move the app's wp-content back.
    exec("mv {$tmp_wpcontent} {$wp_dir}/wp-content", $output, $return);

    if($return) {
      echo "Unable to restore app wp-content\n";

      if(file_exists($tmp_wpcontent)) {
        echo "Whippet has left this in: {$tmp_wpcontent}\n";
      }

      exit(1);
    }

    $this->update_wordpress_lock();
  }
};
