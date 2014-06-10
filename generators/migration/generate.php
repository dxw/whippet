<?php

class MigrationGenerator {
  use whippet_helpers;

  function __construct($options) {
    $this->options = $options;
    $this->manual_fixes = array();
    $this->automatic_fixes = array();
  }

  function generate() {
    $old = $this->options->old;
    $new = $this->options->new;

    if(!file_exists($old)) {
      echo "Unable to find directory: {$old}\n";
    }

    if(!file_exists($new)) {
      echo "Unable to find directory: {$new}\n";
    }

    // TODO: sanity checks
    //  Does $old look like a wp-content?
    //  Does $new look like a whippet app?
    //  Are they both git repos?

    $git = new Git($old);


    //
    // 1.   Get a list of all the submodules. Add entries for any that are wordpress-plugins/whatever.
    // 2. Copy over all the themes
    // 3.   Copy over all plugins that aren't submodules
    //      * Ignore index.php
    //      * Warn about plugin files that are not in a directory
    //      * List all plugins copied into the app.
    // 4.   Ignoring plugins, are there any other submodules? If so, recreated them (in the right place)
    // 5. Copy over whippet-wp-config.php, if it exists
    // 6. List all files that are not dealt with by the above, and prompt user to deal with them.
    //    Don't worry about:
    //      * uploads
    //      * DS_Store
    //      * ...?
    // 7. Summarise what happened to the plugins. For each:
    //    * Was it put in Plugins or copied directly?
    //    * Was it in its own directory? (can we do that automagically?)
    //    * If it's copied directly, does a similar-looking repo exist?
    //    * Is there an inspection for the plugin, and if so, what's the result?
    //

    //
    // PLUGINS & THEMES
    //

    // Fetch submodules first, because we need to bail if any are not in the correct state
    $submodules = $git->submodule_status();

    foreach($submodules as $submodule_dir => $submodule) {
      if(!empty($submodule->status)) {
        echo "Submodule {$submodule->dir} has unmerged changes, is uninitialised, or is not on the index commit. Aborting.\n";
        exit(1);
      }
    }

    // Fetch all the plugins and themes from the old project
    $plugins = $this->get_plugins("{$old}/plugins");
    $themes = $this->get_themes("{$old}/themes");

    $other_files = $this->get_rogue_files($old, $plugins, $themes);

    // Find out which ones are submoduled
    // No need to worry about themes here, because they're not whippet managed. They're dealt with below.
    foreach($plugins as $plugin_file => $plugin_data) {
      foreach($submodules as $submodule_dir => $submodule) {
        // If it looks like a theme, make a note of that for later
        if(dirname($submodule->dir) == "themes") {
          $submodule->theme_dir = basename($submodule->dir);
        }

        // From this point on, we only want plugins
        if(dirname($submodule->dir) != 'plugins' || dirname($plugin_file) != basename($submodule->dir)) {
          continue;
        }

        $plugins[$plugin_file]['is_submodule'] = (isset($submodule->remotes['origin']) && preg_match('/^git@git\.dxw\.net:wordpress-plugins\//', $submodule->remotes['origin']));

        $submodules[$submodule_dir]->plugin_file = $plugin_file;
      }
    }

    // Add submoduled plugins to Plugins file. Remove those submodule & plugin entries.
    foreach($plugins as $plugin_file => $plugin_data) {
      if(!isset($plugin_data['is_submodule']) || !$plugin_data['is_submodule']) {
        continue;
      }

      $plugin_dir = dirname($plugin_file);

      file_put_contents("{$new}/Plugins", "{$plugin_dir}=\n", FILE_APPEND);

      unset($plugins[$plugin_file]);
      unset($submodules["plugins/{$plugin_dir}"]);

      $this->automatic_fixes[] = "Added plugin {$plugin_dir} to the Plugins file";
    }

    // Anything left over will not be Whippet managed. Let's just check that they're all not in wordpress-plugins.
    $available_plugins = file(dirname(__FILE__) . "/share/available-plugins");

    foreach($plugins as $plugin_file => $plugin_data) {
      if(array_search(dirname($plugin_file) . "\n", $available_plugins) !== false) {
        $this->manual_fixes[] = "Non-whippet-managed plugin is available in git: $plugin_file. Should it be Whippet-managed?";
      } else {
        $directory_headers = get_headers("http://www.wordpress.org/plugins/" . dirname($plugin_file) . "/");

        if(preg_match('/200 OK/', $directory_headers[0])) {
          $this->manual_fixes[] = "Non-whippet-managed plugin might be available on the Directory: " . dirname($plugin_file);
        }
      }
    }

    // Re-add submodules that are left-over, and if any are plugins, remove the matching plugin entry
    foreach($submodules as $dir => $submodule) {
      if(count($submodule->remotes['origin']) != 1) {
        $this->manual_fixes[] = "Skipped submodule {$submodule->dir}, because it does not have exactly 1 remote. You'll need to manually add the one you want.";

        unset($submodules[$dir]);

        continue;
      }

      $remote = array_pop($submodule->remotes);

      $git = new Git($new);
      if(!$git->submodule_add($remote, "wp-content/" . $submodule->dir)) {
        // This error will be added to manual fixes later, by seeing if anything is left over in $submodules.
        continue;
      }

      if(isset($submodule->plugin_file)) {
        unset($plugins[$submodule->plugin_file]);

        $this->automatic_fixes[] = "Submoduled plugin from {$remote} at: wp-content/" . $submodule->dir;
      }
      else if(isset($submodule->theme_dir)) {
       unset($themes[$submodule->theme_dir]);

       $this->automatic_fixes[] = "Submoduled theme from {$remote} at: wp-content/" . $submodule->dir;

       if(preg_match('/^twenty/', $submodule->theme_dir)) {
         $this->manual_fixes[] = "Submoduled a default theme from {$remote} at wp-content/{$submodule->dir}. Don't keep it unless it's actually being used.";
       }
      }
      else {
        $this->automatic_fixes[] = "Submodule added at: wp-content/" . $submodule->dir;
      }

      unset($submodules[$dir]);
    }

    // Copy over any plugins that are left over
    foreach($plugins as $plugin_file => $plugin_data) {
      if(dirname($plugin_file) != '.') {
        system("cp -a {$old}/plugins/" . dirname($plugin_file) . " {$new}/wp-content/plugins/");

        $this->automatic_fixes[] = "Copied plugin directory " . dirname($plugin_file) . " into the project";
      }
      else {
        system("cp -a {$old}/plugins/{$plugin_file} {$new}/wp-content/plugins/");

        $this->automatic_fixes[] = "Copied plugin file {$plugin_file} into the project";
      }

      unset($plugins[$plugin_file]);
    }

    // Copy over any themes that are left over
    foreach($themes as $theme_dir => $theme_data) {
      system("cp -a {$old}/themes/" . dirname($theme_dir) . " {$new}/wp-content/themes/");

      $this->automatic_fixes[] = "Copied theme directory {$theme_dir} into the project";

      if(preg_match('/^twenty/', $theme_dir)) {
        $this->manual_fixes[] = "Copied a default theme into the project: {$theme_dir}. Don't keep it unless it's actually being used.";
      }

      unset($themes[$theme_dir]);
    }

    // Make sure there's nothing left
    foreach($plugins as $plugin_file => $plugin_data) {
      $this->manual_fixes[] = "Plugin $plugin_file was not migrated";
    }

    foreach($themes as $theme_dir => $theme_data) {
      $this->manual_fixes[] = "Plugin $theme_dir was not migrated";
    }

    foreach($submodules as $submodule_dir => $submodule) {
      $this->manual_fixes[] = "Submodule $submodule_dir was not migrated";
    }


    //
    // If there are language files, copy them
    //

    if(file_exists("{$old}/languages")) {
      mkdir("{$new}/wp-content/languages/");
      system("cp -a {$old}/languages/*.mo {$new}/wp-content/languages/");

      $this->automatic_fixes[] = "Copied language directory into the project";
    }


    //
    // What happened?
    //

    echo "\n\nApplied these automatic fixes:\n";
    echo "==============================\n";

    foreach($this->automatic_fixes as $fix) {
      echo "  $fix\n";
    }


    echo "\n\nPossible Manual fixes required:\n";
    echo "===============================\n";

    if(!count($this->manual_fixes)) {
      echo "  None.\n";
    }
    else {
      foreach($this->manual_fixes as $fix) {
        echo "  $fix\n";
      }
    }


    echo "\n\nLeft-over files:\n";
    echo "================\n";

    if(!count($other_files)) {
      echo "  None.\n";
    }
    else {
      foreach($other_files as $file) {
        echo "  $file\n";
      }
    }
  }

  function get_themes($dir) {
    $themes = array();

    foreach(new DirectoryIterator($dir) as $file) {
      if($file->isDot() || !$file->isDir()) continue;
      $theme_data = $this->get_file_data("{$dir}/" . $file->getFilename() . "/style.css");

      if(!empty($theme_data['Theme Name'])) {
        $themes[$file->getFilename()] = $theme_data;
      }
    }

    return $themes;
  }

  function get_plugins($dir) {
    $plugins = array();

    foreach(new DirectoryIterator($dir) as $file) {
      if($file->isDot()) continue;

      if($file->isDir()) {
        foreach(new DirectoryIterator("{$dir}/{$file}") as $sub_file) {
          $the_file = "{$file}/" . $sub_file->getFilename();
          $plugin_data = $this->get_file_data("{$dir}/" . $the_file);

          if(!empty($plugin_data['Name'])) {
            $plugins[$the_file] = $plugin_data;
          }
        }
      }
      if(preg_match('/\.php$/', $file->getFilename())) {
        $the_file = $file->getFilename();

        $plugin_data = $this->get_file_data("{$dir}/" . $the_file);

        if(!empty($plugin_data['Name'])) {
          $plugins[$the_file] = $plugin_data;
        }
      }
    }

    return $plugins;
  }

  function get_file_data($plugin_file) {
    $all_headers = array(
      'Name' => 'Plugin Name',
      'Theme Name' => 'Theme Name',
      'PluginURI' => 'Plugin URI',
      'Theme URI' => 'Theme URI',
      'Version' => 'Version',
      'Description' => 'Description',
      'Author' => 'Author',
      'AuthorURI' => 'Author URI',
      'TextDomain' => 'Text Domain',
      'DomainPath' => 'Domain Path',
      'Network' => 'Network',
      // Site Wide Only is deprecated in favor of Network.
      '_sitewide' => 'Site Wide Only',
    );

    $fp = fopen( $plugin_file, 'r' );
    $file_data = fread( $fp, 8192 );
    fclose( $fp );

    $file_data = str_replace( "\r", "\n", $file_data );

    foreach ( $all_headers as $field => $regex ) {
      if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
        $plugin_data[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1] ));
      }
      else {
        $plugin_data[ $field ] = '';
      }
    }

    // Site Wide Only is the old header for Network
    if ( ! $plugin_data['Network'] && $plugin_data['_sitewide'] ) {
      $plugin_data['Network'] = $plugin_data['_sitewide'];
    }

    $plugin_data['Network'] = ( 'true' == strtolower( $plugin_data['Network'] ) );
    unset( $plugin_data['_sitewide'] );

    $plugin_data['Title']      = $plugin_data['Name'];
    $plugin_data['AuthorName'] = $plugin_data['Author'];

    return $plugin_data;
  }

  function get_rogue_files($directory, $plugins, $themes) {
    $rogue_files = array();

    $ignore_paths = array(
      ".git",
      "index.php",
      "plugins/index.php",
      "themes/index.php"
    );

    foreach($themes as $theme_dir => $theme) {
      $ignore_paths[] = "themes/{$theme_dir}";
    }

    foreach($plugins as $plugin_file => $plugin) {
      $path = dirname($plugin_file) == '.' ? basename($plugin_file) : dirname($plugin_file);

      $ignore_paths[] = "plugins/" . $path;
    }

    $iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach(new RecursiveIteratorIterator($iterator) as $filename => $file) {
      $match = false;
      foreach($ignore_paths as $path) {
        $path = realpath("{$directory}/{$path}");

        if(substr(realpath($file->getPathname()), 0, strlen($path)) == $path) {
          $match = true;
          break;
        }
      }

      if(!$match) {
        $rogue_files[] = $file->getPathname();
      }
    }

    return array_unique($rogue_files);
  }

};