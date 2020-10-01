<?php

use Dxw\Whippet\Git\Git;

class ThemeGenerator extends \Dxw\Whippet\WhippetGenerator {
  use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

  protected $wordpress_template_zip = 'https://github.com/dxw/wordpress-template/archive/main.zip';

  private $unique_temp_id;

  function __construct($options) {
    $this->options = $options;

    if(isset($this->options->directory)) {
      $this->target_dir = getcwd() . '/' . $this->options->directory;
    }
    else {
      $this->target_dir = getcwd() . "/whippet-theme";
    }

    $this->unique_temp_id = uniqid();
  }

  function generate() {
    echo "Creating a new whippet theme in {$this->target_dir}\n";

    if(!file_exists($this->target_dir)) {
      mkdir($this->target_dir);
    }

    $this->downloadAndUnzipTemplate();
    $this->copyThemeAndRemoveTemplate();
  }

  private function downloadAndUnzipTemplate()
  {
    $this->download_url_to_file($this->wordpress_template_zip, '/tmp/wordpress_template_' . $this->unique_temp_id . '.zip');
    $this->unzip_to_folder('/tmp/wordpress_template_' . $this->unique_temp_id . '.zip', '/tmp/wordpress_template_' . $this->unique_temp_id);
  }

  private function copyThemeAndRemoveTemplate()
  {   
    $this->recurse_copy('/tmp/wordpress_template_' . $this->unique_temp_id . '/wordpress-template-main/wp-content/themes/theme', $this->target_dir);
    $this->recurse_rm('/tmp/wordpress_template_' . $this->unique_temp_id);
  }
};
