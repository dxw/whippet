<?php

namespace Dxw\Whippet\Modules;

class Dependencies extends \RubbishThorClone
{
    public function __construct()
    {
        parent::__construct();

        $this->factory = new \Dxw\Whippet\Factory();
        $this->projectDirectory = \Dxw\Whippet\ProjectDirectory::find(getcwd());
    }

    public function commands()
    {
        $this->command('install', 'Installs dependencies');
        $this->command('update', 'Updates dependencies to their latest versions');
        $this->command('migrate', 'Converts legacy plugins file to whippet.json');
    }

    private function exitIfError(\Result\Result $result)
    {
        if ($result->isErr()) {
            $this->exitWithError($result->getErr());
        }
    }

    private function exitWithError($error)
    {
        echo sprintf("ERROR: %s\n", $error);
        exit(1);
    }

    private function getDirectory()
    {
        $this->exitIfError($this->projectDirectory);

        return $this->projectDirectory->unwrap();
    }

    public function install()
    {
        $dir = $this->getDirectory();

        $result = $this->getWhippetLock($dir, $this->factory);
        $this->exitIfError($result);
        $lockFile = $result->unwrap();

        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir, $lockFile);

        $this->exitIfError($installer->install());
    }

    public function update()
    {
        $dir = $this->getDirectory();

        $result = $this->getWhippetLock($dir, $this->factory);
        if ($result->isErr()) {
            $lockFile = $this->newWhippetLock($this->factory);
        } else {
            $lockFile = $result->unwrap();
        }

        $updater = new \Dxw\Whippet\Dependencies\Updater($this->factory, $dir, $lockFile);

        $this->exitIfError($updater->update());

        $result = $this->getWhippetLock($dir, $this->factory);
        $this->exitIfError($result);
        $lockFile = $result->unwrap();

        $installer = new \Dxw\Whippet\Dependencies\Installer($this->factory, $dir, $lockFile);
        $this->exitIfError($installer->install());
    }

    public function migrate()
    {
        $dir = new \Dxw\Whippet\ProjectDirectory(getcwd());
        $migration = new \Dxw\Whippet\Dependencies\Migration($this->factory, $dir);
        $this->exitIfError($migration->migrate());
    }

    private function getWhippetLock($dir, $factory)
    {
        $result = $factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock');
        if ($result->isErr()) {
            return \Result\Result::Err(sprintf('whippet.lock: %s', $result->getErr()));
        }
        return $result;
    }

    private function newWhippetLock($factory)
    {
        return $factory->newInstance('\\Dxw\\Whippet\\Files\\WhippetLock', []);
    }
}
