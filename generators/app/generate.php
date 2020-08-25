<?php

class AppGenerator extends \Dxw\Whippet\WhippetGenerator {
  use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;

  protected $wordpress_template_zip = 'https://github.com/dxw/wordpress-template/archive/main.zip';

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
    if(!(new \Dxw\Whippet\Git\Git($this->target_dir))->is_repo()) {
      \Dxw\Whippet\Git\Git::init($this->target_dir);
    }

    echo "Downloading template zip file \n";

    $this->downloadTemplateZip();

    $this->unzipAndRemoveTemplateZip();

    if(isset($this->options->repository)) {
      $this->setWPRepository();
    }

    $this->setWpVersion();

    echo "New whippet app successfully generated at {$this->target_dir} \n";
   }

   private function setWpRepository()
   {
      $appConfig = $this->target_dir . '/config/application.json';
      $data = json_decode(file_get_contents($appConfig), JSON_OBJECT_AS_ARRAY);
      $data['wordpress']['repository'] = $this->options->repository;
      file_put_contents($appConfig, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
   }

   private function setWpVersion()
   {
       $appConfig = $this->target_dir . '/config/application.json';
       $data = json_decode(file_get_contents($appConfig), JSON_OBJECT_AS_ARRAY);
       $data['wordpress']['revision'] = $this->getLatest();
       file_put_contents($appConfig, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
   }

   private function getLatest()
   {
       $versionCheck = json_decode(file_get_contents('https://api.wordpress.org/core/version-check/1.7/'), JSON_OBJECT_AS_ARRAY);
       return $versionCheck['offers'][0]['version'];
   }

   private function downloadTemplateZip()
   {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->wordpress_template_zip);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $data = curl_exec ($ch);
      curl_close ($ch);
      $file = fopen($this->target_dir . "/wordpress_template.zip", "w+");
      fputs($file, $data);
      fclose($file);
   }

   private function unzipAndRemoveTemplateZip()
   {
      $zip = new ZipArchive;
      $res = $zip->open($this->target_dir . "/wordpress_template.zip");
      if ($res === TRUE) {
          $zip->extractTo($this->target_dir); // directory to extract contents to
          $zip->close();
          echo "Template .zip extracted \n";
          unlink($this->target_dir . "/wordpress_template.zip");
          echo "Template .zip deleted \n";
      } else {
          echo "Template unzip failed \n, error code: " . $res;
      }
      $this->recurse_copy($this->target_dir . '/wordpress-template-main', $this->target_dir);
      $this->recurse_rm($this->target_dir . '/wordpress-template-main');
   }
};
