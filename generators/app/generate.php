<?php

class AppGenerator extends \Dxw\Whippet\WhippetGenerator {
  use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

  protected $wordpress_template_zip = 'https://github.com/dxw/wordpress-template/archive/main.zip';
  private $target_dir;
  private $options = array();

  function __construct($options) {
    $this->options = $options;

    if(isset($this->options->directory)) {
      $this->target_dir = $this->options->directory;
    }
    else {
      $this->target_dir = getcwd() . "/whippet-app";
    }
  }


  function generate() {
    echo "Creating a new whippet application in {$this->target_dir}\n";

    if(!file_exists($this->target_dir)) {
      mkdir($this->target_dir);
    }

    // Make the target dir a git repo, if it isn't already
    $target_repo = new \Dxw\Whippet\Git\Git($this->target_dir);
    if(!$target_repo->is_repo()) {
      \Dxw\Whippet\Git\Git::init($this->target_dir);
    }

    echo "Downloading and unzipping template file \n";

    $this->downloadTemplateZip();

    $this->unzipAndRemoveTemplateZip();

    if(isset($this->options->repository)) {
      $this->setWPRepository();
    }

    /* zip archives don't preserve permissions, so fix those */
    exec("chmod 0755 " . $this->target_dir . "/setup/*");
    exec("chmod 0755 " . $this->target_dir . "/script/*");
    exec("chmod 0755 " . $this->target_dir . "/bin/*");

    // Whippet deploy requires at least one commit in the repo.
    if (!$target_repo->current_commit()) {
      $target_repo->add("--all");
      $target_repo->commit("Initial commit from Whippet");
    }

    echo "New whippet app successfully generated at {$this->target_dir} \n";
  }

  private function setWpRepository()
  {
    $appConfig = $this->target_dir . '/config/application.json';
    $data = json_decode(file_get_contents($appConfig), JSON_OBJECT_AS_ARRAY);
    $data['wordpress']['repository'] = $this->options->repository;
    file_put_contents($appConfig, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
  }

  private function getLatest()
  {
    $versionCheck = json_decode(file_get_contents('https://api.wordpress.org/core/version-check/1.7/'), JSON_OBJECT_AS_ARRAY);
    return $versionCheck['offers'][0]['version'];
  }

  private function downloadTemplateZip()
  {
    $this->download_url_to_file($this->wordpress_template_zip, $this->target_dir . "/wordpress_template.zip");
  }

  private function unzipAndRemoveTemplateZip()
  {
    $this->unzip_to_folder($this->target_dir . "/wordpress_template.zip", $this->target_dir);
    // Move the unzipped contents out of the containing zip folder
    $this->recurse_copy($this->target_dir . '/wordpress-template-main', $this->target_dir);
    $this->recurse_rm($this->target_dir . '/wordpress-template-main');
  }
};
