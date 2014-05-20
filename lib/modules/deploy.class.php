<?php

class Release {
  use whippet_helpers;

  public $number = 0;
  public $time = 0;

  function __construct($releases_dir, $message, $number) {
    $this->number = $number;
    $this->time = date('r');
    $this->release_dir = "{$releases_dir}/{$number}";

    $this->whippet_init();
  }

  function create() {
    //    1. Clone WP
    //    2. Delete wp-content etc
    //    3. Copy our wp-content, omitting gitfoo
    //    4. ?? Theme/plugin build steps ?? (Makefile-esque thing?)
    //    5. Symlink required files from shared dir

    // Create a new directory for this release, or use only an empty existing dir
    $this->check_and_create_dir($this->release_dir, true);

    // Clone WP and remove things we don't want
    $wp = new Git($this->release_dir);
    $wp->clone_repo($this->application_config->wordpress->repository);
    $wp->checkout($this->application_config->wordpress->revision);

    foreach(['wp-content', '.git', 'readme.html', 'wp-config-sample.php'] as $delete) {
      // TODO: Sorry, Windows devs
      system("rm -rf {$this->release_dir}/$delete");
    }

    // Copy over wp-content
    // TODO: Sorry, windows devs
    system("cp -r {$this->project_dir}/wp-content {$this->release_dir}/wp-content");


    //
    // TODO: theme and plugin build steps
    //


    // Symlinkery
    // TODO: Sorry, windows devs
    system("ln -s " . realpath("{$this->release_dir}/../../shared/wp-config.php") . " {$this->release_dir}/wp-config.php");
    system("ln -s " . realpath("{$this->release_dir}/../../shared/uploads") . " {$this->release_dir}/wp-content/uploads");

    // FIN
  }
};

class Deploy {
  use whippet_helpers;

  function __construct($dir) {
    $this->deploy_dir   = $dir;
    $this->releases_dir = "{$this->deploy_dir}/releases";
    $this->shared_dir   = "{$this->deploy_dir}/shared";
  }

  function deploy() {
    try {
      //
      // 1. Make sure the target directory does not exist (or exists and is empty)
      // 2. Load or create the releases manifest
      // 3. Deploy the app into the releases directory
      // 4. Validate the deploy
      // 5. Create or update the "current" symlink
      //


      //
      // Make sure the environment is sane
      //

      $this->check_and_create_dir($this->deploy_dir);
      $this->check_and_create_dir($this->releases_dir);
      $this->check_and_create_dir($this->shared_dir);


      //
      // Load up the manifest and create the new release
      //

      $this->load_releases_manifest();

      // TODO: add support for a release message
      if(count($this->releases_manifest)) {
        $release_number = $this->releases_manifest[count($this->releases_manifest)-1]->number + 1;
      }
      else {
        $release_number = 0;
      }

      $new_release = new Release($this->releases_dir, "", $release_number);


      // Make it.
      $new_release->create();


      //
      // Did everything work?
      //

      //
      // Is WP installed?
      //

      $checks = [
        //
        // Is WP there?
        //

        "wp-login.php is missing; is WordPress properly deployed?" => !file_exists("{$new_release->release_dir}/wp-login.php"),
        "wp-includes/wp-db.php is missing; is WordPress properly deployed?" => !file_exists("{$new_release->release_dir}/wp-includes/wp-db.php"),
        "wp-admin/edit.php is missing; is WordPress properly deployed?" => !file_exists("{$new_release->release_dir}/wp-admin/edit.php"),

        //
        // Is the app there?
        //

        "wp-content/themes is missing; is the app properly deployed?" => !file_exists("{$new_release->release_dir}/wp-content/themes"),
        "wp-content/plugins is missing; is the app properly deployed?" => !file_exists("{$new_release->release_dir}/wp-content/plugins"),


        // Is there stuff in shared? Does it look right?
        "wp-config.php is not in the shared directory." => !file_exists("{$new_release->release_dir}/../../shared/wp-config.php"),
        "uploads directory is not in the shared directory." => !file_exists("{$new_release->release_dir}/../../shared/uploads"),
        "wp-config.php doesn't contain DB_NAME; is it valid?" => !strpos(file_get_contents("{$new_release->release_dir}/../../shared/wp-config.php"), "DB_NAME"),

        //
        // Did the symlinking work?
        //

        "wp-config.php is missing; did the symlinking fail?" => !file_exists("{$new_release->release_dir}/wp-config.php"),
        "wp-content/uploads is missing; did the symlinking fail?" => !file_exists("{$new_release->release_dir}/wp-content/uploads"),
      ];

      $release_ok = true;
      $messages = [];

      foreach($checks as $message => $failed) {
        if($failed) {
          $release_ok = false;
          $messages[] = "\t{$message}";
        }
      }


      //
      // If it was all ok:
      //

      if(!$release_ok) {
        $broken_release = $broken_release_prefix = "{$new_release->release_dir}.broken";
        $count = 1;

        while(file_exists($broken_release)) {
          $broken_release = $broken_release_prefix . "_{$count}";
        }

        system("mv {$new_release->release_dir} {$broken_release}");

        echo "Problems:\n";
        echo implode($messages, "\n");
        echo "Release did not validate; it has been moved to: $broken_release";

      }
      else{
        $current = "{$new_release->release_dir}/../../current";
        if(file_exists($current)) {
          system("rm {$current}");
        }

        system("ln -s " . realpath("{$new_release->release_dir}") . " {$current}");

        // Update manifest
        $release = new stdClass();
        $release->time = $new_release->time;
        $release->number = $new_release->number;

        $this->releases_manifest[] = $release;
        $this->save_releases_manifest();
      }
    }
    catch(Exception $e) {
      die($e->getMessage());
    }
  }

  protected function load_releases_manifest(){
    $releases_manifest_file = "{$this->deploy_dir}/releases/manifest.json";

    if(!file_exists($releases_manifest_file)) {
      $this->releases_manifest = array();
    }
    else {
      $this->releases_manifest = json_decode(file_get_contents($releases_manifest_file));
    }

    // TODO: handle invalid json properly
    // http://www.php.net/manual/en/function.json-last-error.php
    if(!is_array($this->releases_manifest)) {
      echo "Unable to parse releases manifest";
      exit(1);
    }
  }

  protected function save_releases_manifest(){
    return file_put_contents("{$this->deploy_dir}/releases/manifest.json", json_encode($this->releases_manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }
};


/*
/var/data/dxw.com     # app repo

app
  releases
    c1cbf7b2f8ecd7d6befece09f712c85a7c839b      # a working WP deploy
  shared
    wp-config.php
    uploads -> /somewhere/that/uploads/exist
  current -> c1cbf7b2f8ecd7d6befece09f712c85a7c839b

/var/vhosts/dxw.com -> /app/current



git pull
whippet deploy /path/to/app
*/