<?php

require WHIPPET_ROOT . "/generators/whippet_generator.class.php";

class MigrationGenerator extends WhippetGenerator {
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
    // Check that all submodules are well formed
    //

    echo "Loading submodules\n";
    $submodules = $git->submodule_status();

    foreach($submodules as $submodule_dir => $submodule) {
      if(!empty($submodule->status)) {
        echo "Submodule {$submodule->dir} has unmerged changes, is uninitialised, or is not on the index commit. Aborting.\n";
        exit(1);
      }
    }


    //
    // Fetch all the files, plugins and themes from the old project, and work out which are submoduled
    //

    echo "Loading plugins\n";

    $plugins = $this->get_plugins("{$old}/plugins");

    echo "Loading themes\n";
    $themes = $this->get_themes("{$old}/themes");

    echo "Loading everything else\n";
    $other_files = $this->get_rogue_files($old, $plugins, $themes);

    // Find out which ones are submoduled
    // No need to worry about themes here, because they're not whippet managed. They're dealt with below.
    echo "Looking for Whippet manageable plugins\n";
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


    //
    // Add submoduled plugins to Plugins file. Remove those submodule & plugin entries.
    //

    // Always start with a fresh file
    // Whippet init adds akismet (as it is part of the WP distro) but we only want it now if it's in $old
    file_put_contents("{$new}/plugins", "source = \"git@git.dxw.net:wordpress-plugins/\"\n");

    echo "Updating Plugins file\n";
    foreach($plugins as $plugin_file => $plugin_data) {
      if(!isset($plugin_data['is_submodule']) || !$plugin_data['is_submodule']) {
        continue;
      }

      $plugin_dir = dirname($plugin_file);

      file_put_contents("{$new}/plugins", "{$plugin_dir}=\n", FILE_APPEND);

      unset($plugins[$plugin_file]);
      unset($submodules["plugins/{$plugin_dir}"]);

      $this->automatic_fixes[] = "Added plugin {$plugin_dir} to the Plugins file";
    }


    //
    // Check non-Whippet-managed plugins so we can warn if they are on directory or in auto-wordpress
    //

    $available_plugins = file(dirname(__FILE__) . "/share/available-plugins");

    echo "Sanity-checking left over plugins\n";

    foreach($plugins as $plugin_file => $plugin_data) {
      if(array_search(dirname($plugin_file) . "\n", $available_plugins) !== false) {
        $this->manual_fixes[] = "Non-whippet-managed plugin is available in git: $plugin_file. Should it be Whippet-managed?";
      } else {
        $plugin_slug = dirname($plugin_file);

        if($plugin_slug == '.') {
          $plugin_slug = preg_replace("/\.php$/", '', $plugin_file);
        }

        $directory_headers = get_headers("http://www.wordpress.org/plugins/{$plugin_slug}/");

        if(preg_match('/200 OK/', $directory_headers[0])) {
          $this->manual_fixes[] = "Non-whippet-managed plugin might be available on the Directory: " . dirname($plugin_file);
        }
      }
    }


    //
    // Re-add submodules that are left-over, and if any are plugins, remove the matching plugin entry
    //

    echo "Adding submodules\n";
    foreach($submodules as $dir => $submodule) {
      if(count($submodule->remotes['origin']) != 1) {
        $this->manual_fixes[] = "Skipped submodule {$submodule->dir}, because it does not have exactly 1 remote. You'll need to manually add the one you want.";

        unset($submodules[$dir]);
        continue;
      }

      $remote = array_pop($submodule->remotes);

      if(!empty($submodule->theme_dir)) {
        if(!$this->is_parent_theme($themes[$submodule->theme_dir]) && array_search($submodule->theme_dir, array("twentyten", "twentyeleven", "twentytwelve", "twentythirteen", "twentyfourteen")) !== false) {
          $this->manual_fixes[] = "Refusing to submodule a default theme from {$remote} at wp-content/{$submodule->dir}. Add a submodule for this theme manually if it is required.";

          unset($submodules[$dir]);
          unset($themes[$submodule->theme_dir]);
          continue;
        }
        else if($this->is_parent_theme($themes[$submodule->theme_dir])) {
          $this->manual_fixes[] = "Submoduled a default theme from {$remote} at wp-content/{$submodule->dir}, because it is a parent of: " . implode(', ', $themes[$submodule->theme_dir]['children']);
        }
      }

      $git = new Git($new);
      if(!$git->submodule_add($remote, "wp-content/" . $submodule->dir)) {
        // This error will be added to manual fixes later, by seeing if anything is left over in $submodules.
        continue;
      }

      if(strpos($remote, "git@git.dxw.net") === false) {
        $this->manual_fixes[] = "Non-dxw git repo submoduled: {$remote} at {$submodule->dir}";
      }

      if(isset($submodule->plugin_file)) {
        unset($plugins[$submodule->plugin_file]);

        $this->automatic_fixes[] = "Submoduled plugin from {$remote} at: wp-content/" . $submodule->dir;
      }
      else if(isset($submodule->theme_dir)) {
        unset($themes[$submodule->theme_dir]);

        $this->automatic_fixes[] = "Submoduled theme from {$remote} at: wp-content/" . $submodule->dir;
      }
      else {
        $this->automatic_fixes[] = "Submodule added at: wp-content/" . $submodule->dir;
      }

      unset($submodules[$dir]);
    }


    //
    // Copy over any plugins that are left over
    //

    echo "Copying project plugins\n";
    foreach($plugins as $plugin_file => $plugin_data) {
      if(dirname($plugin_file) != '.') {
        $this->recurse_copy("{$old}/plugins/" . dirname($plugin_file), "{$new}/wp-content/plugins/" . dirname($plugin_file));

        $this->automatic_fixes[] = "Copied plugin directory " . dirname($plugin_file) . " into the project";
      }
      else {
        $this->recurse_copy("{$old}/plugins/{$plugin_file}", "{$new}/wp-content/plugins");

        $this->automatic_fixes[] = "Copied plugin file {$plugin_file} into the project";
      }

      unset($plugins[$plugin_file]);
    }


    //
    // Copy over any themes that are left over
    //

    echo "Copying project themes\n";
    foreach($themes as $theme_dir => $theme_data) {
      if(!$this->is_parent_theme($theme_data) && array_search($theme_dir, array("twentyten", "twentyeleven", "twentytwelve", "twentythirteen", "twentyfourteen")) !== false) {
        $this->manual_fixes[] = "Refusing to copy a default theme into the project: {$theme_dir}. Copy it manually if you need it.";

        unset($themes[$theme_dir]);
        continue;
      }
      else if($this->is_parent_theme($theme_data)) {
        $this->manual_fixes[] = "Copied theme directory {$theme_dir} into the project, because it is a parent of: " . implode(', ', $theme_data['children']);
      }

      $new_theme_dir = "{$new}/wp-content/themes/" . dirname($theme_dir);

      if(!file_exists($new_theme_dir)) {
        system("mkdir -p $new_theme_dir"); // For themes within subdirs
      }

      $this->recurse_copy("{$old}/themes/{$theme_dir}","{$new_theme_dir}/{$theme_dir}");

      $this->automatic_fixes[] = "Copied theme directory {$theme_dir} into the project";

      unset($themes[$theme_dir]);
    }


    //
    // Check that we don't have any themes, plugins or submodules left over.
    //

    echo "Checking for unhandled plugins, themes and submodules\n";

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
      echo "Copying language files\n";

      mkdir("{$new}/wp-content/languages/");
      foreach (glob("{$old}/languages/*.mo") as $file) {
        copy($file, "{$new}/wp-content/languages/{$file}");
      }

      $this->automatic_fixes[] = "Copied language directory into the project";
    }


    //
    // What happened?
    //

    $results = "";

    $results .= "\n\nApplied these automatic fixes:\n";
    $results .= "==============================\n";

    foreach($this->automatic_fixes as $fix) {
      $results .= "  $fix\n";
    }


    $results .= "\n\nNotices/possible manual fixes:\n";
    $results .= "==============================\n";

    if(!count($this->manual_fixes)) {
      $results .= "  None.\n";
    }
    else {
      foreach($this->manual_fixes as $fix) {
        $results .= "  $fix\n";
      }
    }


    $results .= "\n\nLeft-over files:\n";
    $results .= "================\n";

    if(!count($other_files)) {
      $results .= "  None.\n";
    }
    else {
      foreach($other_files as $file) {
        $results .= "  $file\n";
      }
    }

    file_put_contents("{$new}/migration.log", $results);
    echo $results;
  }

  function is_parent_theme($theme) {
    return count($theme['children']) !== 0;
  }

  function get_themes($dir) {
    $themes = array();

    foreach(new DirectoryIterator($dir) as $file) {
      if($file->isDot() || !$file->isDir()) continue;

      $got_one = false;

      $styles = "{$dir}/" . $file->getFilename() . "/style.css";

      if(file_exists($styles)) {
        $theme_data = $this->get_file_data($styles);

        if(!empty($theme_data['Theme Name'])) {
          $themes[$file->getFilename()] = $theme_data;
          $got_one = true;
        }
      }
      else {
        foreach(new DirectoryIterator($file->getPathname()) as $sub_file) {
          if($sub_file->isDot() || !$sub_file->isDir()) continue;

          $styles = "{$dir}/" . $file->getFilename() . "/" . $sub_file->getFilename() . "/style.css";

          if(file_exists($styles)) {
            $theme_data = $this->get_file_data($styles);

            if(!empty($theme_data['Theme Name'])) {
              $themes[$file->getFilename() . '/' . $sub_file->getFilename()] = $theme_data;

              $got_one = true;
            }
          }
        }
      }

      if(!$got_one) {
        $this->manual_fixes[] = "Unable to find a theme in " . $file->getPathname() . ". Is there one there?";
      }
    }

    foreach($themes as $parent_theme_dir => $parent_theme_data) {
      $themes[$parent_theme_dir]['children'] = array();

      foreach($themes as $theme_dir => $theme_data) {
        if($parent_theme_dir == $theme_data['Template']) {
          $themes[$parent_theme_dir]['children'][] = $theme_dir;
        }
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
      else if(preg_match('/\.php$/', $file->getFilename())) {
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
      'Template' => 'Template',
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

    foreach($ignore_paths as $k => $path) {
      $ignore_paths[$k] = realpath("{$directory}/{$path}");
    }

    $iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach(new RecursiveIteratorIterator($iterator) as $filename => $file) {
      $match = false;
      foreach($ignore_paths as $path) {
        if(strpos($file->getRealPath(), $path) === 0) {
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
