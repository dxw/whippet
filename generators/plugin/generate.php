<?php

use Dxw\Whippet\Git\Git;

class PluginGenerator extends \Dxw\Whippet\WhippetGenerator {
  use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

  protected $plugin_template_zip = 'https://github.com/dxw/wordpress-plugin-template/archive/main.zip';

  private $unique_temp_id;
  private $target_dir;
  private $options = array();

  function __construct($options) {
    $this->options = $options;

    if(isset($this->options->directory)) {
      $this->target_dir = getcwd() . '/' . $this->options->directory;
    }
    else {
      $this->target_dir = getcwd() . "/whippet-plugin";
    }

    $this->unique_temp_id = uniqid();
  }

  function generate() {
    echo "Creating a new whippet plugin in {$this->target_dir}\n";

    if(!file_exists($this->target_dir)) {
      mkdir($this->target_dir);
    }

    $this->downloadAndUnzipTemplate();
    $this->copyThemeAndRemoveTemplate();
  }

  private function downloadAndUnzipTemplate()
  {
    $this->download_url_to_file($this->plugin_template_zip, '/tmp/plugin_template_' . $this->unique_temp_id . '.zip');
    $this->unzip_to_folder('/tmp/plugin_template_' . $this->unique_temp_id . '.zip', '/tmp/plugin_template_' . $this->unique_temp_id);
  }

  private function copyThemeAndRemoveTemplate()
  {
    $this->recurse_copy('/tmp/plugin_template_' . $this->unique_temp_id . '/wordpress-plugin-template-main', $this->target_dir);
    copy('/tmp/plugin_template_' . $this->unique_temp_id . '/wordpress-plugin-template-main/.gitignore', $this->target_dir . '/.gitignore');
    if(isset($this->options->nogitignore)) {
      unlink($this->target_dir . '/.gitignore');
    }
    $this->recurse_rm('/tmp/plugin_template_' . $this->unique_temp_id);
  }
};
