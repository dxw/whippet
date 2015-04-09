<?php

class Build extends RubbishThorClone {
  use manifest_io;
  use whippet_helpers;

  public function commands() {
  }

  /*
   * Commands
   */

  /*
   * TODO: document
   */
  public function start($argv) {
    $this->whippet_init();

    $patterns = [
      'Gruntfile.js',
      '*/Gruntfile.js',
      '*/*/Gruntfile.js',
      '*/*/*/Gruntfile.js',
      '*/*/*/*/Gruntfile.js',
    ];

    $gruntfiles = [];
    foreach ($patterns as $pattern) {
      foreach (glob($pattern) as $file) {
        $gruntfiles[] = $file;
      }
    }

    printf("Found %d files:\n", count($gruntfiles));
    foreach ($gruntfiles as $file) {
      printf("* %s\n", $file);
    }

    $names = [];

    foreach ($gruntfiles as $file) {
      $dir = dirname($file);

      $name = 'whippet_build_'.preg_replace('/[^a-zA-Z0-9]/', '_', $this->project_dir.'/'.$dir);
      $names[] = $name;

      $cmd = 'docker run -d --name='.escapeshellarg($name).' -v '.escapeshellarg($this->project_dir).':/app --workdir=/app/'.escapeshellarg($dir).' node sh -c "npm install -g grunt-cli && npm install --no-bin-links && grunt watch"';
      echo $cmd."\n";
      passthru($cmd);
    }

    $names = implode(' ', array_map('escapeshellarg', $names));
    passthru('docker wait '.$names);
  }
}
