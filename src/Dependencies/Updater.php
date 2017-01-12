<?php

namespace Dxw\Whippet\Dependencies;

class Updater
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\ProjectDirectory $dir
    ) {
        $this->factory = $factory;
        $this->dir = $dir;
    }

    public function update($dep = null)
    {
        $result = $this->loadWhippetFiles();

        if ($result->isErr()) {
            return $result;
        }

        if (is_null($dep)) {
            return $this->updateAll();
        }
        return $this->updateDependency($dep);
    }

    private function updateDependency($dep)
    {
        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
        if ($result->isErr()) {
            echo "No whippet.lock file exists, you need to run `whippet deps update` to generate one before you can update a specific dependency. \n";
            return \Result\Result::err(sprintf('whippet.lock: %s', $result->getErr()));
        }

        if (strpos($dep, '/') === false) {
            echo "Dependency should be in format [type]/[name]. \n";
            return \Result\Result::err('Incorrect dependency format');
        }

        $type = explode('/', $dep)[0];
        $name = explode('/', $dep)[1];
        $depArray = $this->jsonFile->getDependency($type, $name);
        if ($depArray === []) {
            return \Result\Result::err('No matching dependency in whippet.json');
        }

        $this->updateHash();
        $this->loadGitignore();

        echo sprintf("[Updating %s/%s]\n", $type, $depArray['name']);
        $result = $this->addDependencyToLockfile($type, $depArray);
        if ($result->isErr()) {
            return $result;
        }

        foreach (['themes', 'plugins'] as $type) {
            foreach ($this->jsonFile->getDependencies($type) as $dep) {
                $this->addDependencyToGitignore($type, $dep['name']);
            }
        }
        $this->lockFile->saveToPath($this->dir.'/whippet.lock');
        $this->gitignore->save_ignores(array_unique($this->ignores));

        return \Result\Result::ok();
    }

    private function updateAll()
    {
        $this->updateHash();
        $this->loadGitignore();

        $count = 0;

        foreach (['themes', 'plugins'] as $type) {
            foreach ($this->jsonFile->getDependencies($type) as $dep) {
                echo sprintf("[Updating %s/%s]\n", $type, $dep['name']);

                $result = $this->addDependencyToLockfile($type, $dep);
                if ($result->isErr()) {
                    return $result;
                }

                $this->addDependencyToGitignore($type, $dep['name']);

                ++$count;
            }
        }

        $this->lockFile->saveToPath($this->dir.'/whippet.lock');
        $this->gitignore->save_ignores(array_unique($this->ignores));

        if ($count === 0) {
            echo "whippet.json contains no dependencies\n";
        }

        return \Result\Result::ok();
    }

    private function loadWhippetFiles()
    {
        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $this->dir.'/whippet.json');
        if ($result->isErr()) {
            return \Result\Result::err(sprintf('whippet.json: %s', $result->getErr()));
        }
        $this->jsonFile = $result->unwrap();

        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
        if ($result->isErr()) {
            $this->lockFile = $this->factory->newInstance('\\Dxw\\Whippet\\Files\\WhippetLock', []);
        } else {
            $this->lockFile = $result->unwrap();
        }

        return \Result\Result::ok();
    }

    private function updateHash()
    {
        $jsonHash = sha1(file_get_contents($this->dir.'/whippet.json'));
        $this->lockFile->setHash($jsonHash);
    }

    private function loadGitignore()
    {
        $this->gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', (string) $this->dir);

        $this->ignores = [];
        if (is_file($this->dir.'/.gitignore')) {
            $this->ignores = $this->gitignore->get_ignores();
        }

        // Iterate through locked dependencies and remove from gitignore
        foreach (['themes', 'plugins'] as $type) {
            foreach ($this->lockFile->getDependencies($type) as $dep) {
                $line = $this->getGitignoreDependencyLine($type, $dep['name']);
                $index = array_search($line, $this->ignores);
                if ($index !== false) {
                    unset($this->ignores[$index]);
                }
            }
        }
    }

    private function addDependencyToGitignore($type, $name)
    {
        $this->ignores[] = $this->getGitignoreDependencyLine($type, $name);
    }

    private function getGitignoreDependencyLine($type, $name)
    {
        return '/wp-content/'.$type.'/'.$name."\n";
    }

    private function addDependencyToLockfile($type, array $dep)
    {
        if (isset($dep['src'])) {
            $src = $dep['src'];
        } else {
            $sources = $this->jsonFile->getSources();
            if (!isset($sources[$type])) {
                return \Result\Result::err('missing sources');
            }
            $src = $sources[$type].$dep['name'];
        }

        $ref = 'master';
        if (isset($dep['ref'])) {
            $ref = $dep['ref'];
        }

        $commitResult = $this->factory->callStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', $src, $ref);

        if ($commitResult->isErr()) {
            return \Result\Result::err(sprintf('git command failed: %s', $commitResult->getErr()));
        }

        $this->lockFile->addDependency($type, $dep['name'], $src, $commitResult->unwrap());

        return \Result\Result::ok();
    }
}
