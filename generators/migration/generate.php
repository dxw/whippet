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
    //    * Ignore index.php
    //    * Warn about plugin files that are not in a directory
    //    * List all plugins copied into the app. [Perhaps: warn if it looks like a repo exists for it]
    // 4. Copy over whippet-wp-config.php, if it exists
    // 5. List all files that are not dealt with by the above, and prompt user to deal with them.
    //    Don't worry about:
    //      * uploads
    //      * DS_Store
    //      * ...?
    //
   }
};