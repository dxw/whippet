<?php

namespace Dxw\Whippet\Modules;

class Dependencies extends \RubbishThorClone
{
    public function __construct()
    {
        parent::__construct();

        $this->factory = new \Dxw\Whippet\Factory();
        $this->projectDirectory = \Dxw\Whippet\ProjectDirectory::find(getcwd());
        $base_api = new \Dxw\Whippet\Services\BaseApi();
        $json_api = new \Dxw\Whippet\Services\JsonApi($base_api);
        $inspections_api_host = 'https://security.dxw.com';
        $inspections_api_path = '/wp-json/v1/inspections/';
        $inspections_api = new \Dxw\Whippet\Services\InspectionsApi($inspections_api_host, $inspections_api_path, $json_api);
        $this->inspectionChecker = new \Dxw\Whippet\Services\InspectionChecker($inspections_api);
    }

    public function commands()
    {
        $this->command('install', 'Installs dependencies');
        $this->command('update', 'Updates dependencies to their latest versions. Use deps update [type]/[name] to update a specific dependency');
        $this->command('migrate', 'Converts legacy plugins file to whippet.json');
    }

    private function exitIfError(\Result\Result $result)
    {
        if ($result->isErr()) {
            echo sprintf("ERROR: %s\n", $result->getErr());
            exit(1);
        }
    }

    private function getDirectory()
    {
        $this->exitIfError($this->projectDirectory);

        return $this->projectDirectory->unwrap();
    }

    public function install()
    {
        $dir = $this->getDirectory();
        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir, $this->inspectionChecker);

        $this->exitIfError($installer->installAll());
    }

    public function update($dep = null)
    {
        $dir = $this->getDirectory();
        $updater = new \Dxw\Whippet\Dependencies\Updater($this->factory, $dir);
        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir, $this->inspectionChecker);

        if (is_null($dep)) {
            $this->exitIfError($updater->updateAll());
            $this->exitIfError($installer->installAll());
        } else {
            $this->exitIfError($updater->updateSingle($dep));
            $this->exitIfError($installer->installSingle($dep));
        }
    }

    public function migrate()
    {
        $dir = new \Dxw\Whippet\ProjectDirectory(getcwd());
        $migration = new \Dxw\Whippet\Dependencies\Migration($this->factory, $dir);
        $this->exitIfError($migration->migrate());
    }
}
