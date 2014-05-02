<?php
class WordPress extends RubbishThorClone {
  use manifest_io;

  public function commands() {
    $this->command('install', 'Deploys the specified WordPress version');
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

    // Do we have wp already? If not, do a checkoutless clone and move .git into the wp dir
    // We can't just clone because our wp-content means the dir is non-empty
    if(!$git->is_repo()) {
      if(!$git->clone_no_checkout($this->wordpress->repository)) {
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
      if(!$git->hard_reset($this->wordpress->revision)) {
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
      if(!$git->checkout($this->wordpress->revision)) {
        // Undo the move
        exec("mv {$tmp_wpcontent} {$wp_dir}/wp-content", $output, $return);
        exit(1);
      }
    }

    // Now we have the core files and our app in a temp directory. So get rid
    // of the core files we don't want, and move the directory back. Deleting
    // the distro wp-content from the index is useful because we can then use
    // git to detect changes to the core files

    if(!$git->rm("wp-content", true)) {
      echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
      exit(1);
    }

    // Ignore the app's wp-content
    file_put_contents("{$wp_dir}/.gitignore", "/wp-content\n");

    // Add it to the commit (perhaps not really necessary, but never mind)
    if(!$git->add(".gitignore")) {
      echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
      exit(1);
    }

    // Commit!
    // NB: This repo will never be pushed anywhere, and if we change the revision
    // we want, these commits will just get left behind. This is ok.
    if(!$git->commit("Remove upstream wp-content and ignore app wp-content")) {
      echo "Whippet was not able to recover from this error. You should remove the WordPress core files from ./wordpress (preserving your wp-content) and re-install WordPress.\n";
      exit(1);
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
  }
};
