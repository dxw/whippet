<?php

class MigrationGenerator {
  use whippet_helpers;

  function __construct($options) {
    $this->options = $options;
  }

  function generate() {
    //
    // 1. Get a list of all the submodules. Add entries for any that are wordpress-plugins/whatever.
    // 2. Copy over all the themes
    // 3. Copy over all plugins that aren't submodules
    // 4. Copy over whippet-wp-config.php, if it exists
    // 5. List all files that are not dealt with by the above, and prompt user to deal with them.
    //
   }
};