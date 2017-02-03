<?php

namespace Dxw\Whippet\Dependencies;

use Dxw\Whippet\Models\Dependency;

class Updater
{
    public function __construct(
        \Dxw\Whippet\Factory $factory,
        \Dxw\Whippet\ProjectDirectory $dir
    ) {
        $this->factory = $factory;
        $this->dir = $dir;
    }

    public function updateSingle($input_dep)
    {
        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
        if ($result->isErr()) {
            echo "No whippet.lock file exists, you need to run `whippet deps update` to generate one before you can update a specific dependency. \n";
            return \Result\Result::err(sprintf('whippet.lock: %s', $result->getErr()));
        }

        if (strpos($input_dep, '/') === false) {
            echo "Dependency should be in format [type]/[name]. \n";
            return \Result\Result::err('Incorrect dependency format');
        }

        $type = explode('/', $input_dep)[0];
        $name = explode('/', $input_dep)[1];

        $result = $this->loadWhippetFiles();
        if ($result->isErr()) {
            return $result;
        }

        $dep = $this->jsonFile->getDependency($type, $name);
        if ($dep === []) {
            return \Result\Result::err('No matching dependency in whippet.json');
        }

        $lockedDependency = $this->lockFile->getDependency($type, $name);

        $sources = $this->jsonFile->getSources();
        if (isset($sources[$type])) {
            $default_repo = $sources[$type];
        } else {
            $default_repo = null;
        }

        return $this->update([new Dependency($dep, $type, $default_repo)], [$lockedDependency]);
    }

    public function updateAll()
    {
        $result = $this->loadWhippetFiles();
        if ($result->isErr()) {
            return $result;
        }

        $sources = $this->jsonFile->getSources();

        $allDependencies = array();

        foreach (['themes', 'plugins'] as $type) {
            if (isset($sources[$type])) {
                $default_repo = $sources[$type];
            } else {
                $default_repo = null;
            }

            $typeDeps = $this->jsonFile->getDependencies($type);
            $typeDependencies = array_map(function ($dep) use ($type, $default_repo) {
                return new Dependency($dep, $type, $default_repo);
            }, $typeDeps);
            $allDependencies = array_merge($allDependencies, $typeDependencies);
        }

        if (count($allDependencies) > 0) {
            $lockedDependencies = $this->getLockedDependencies($this->lockFile);
            return $this->update($allDependencies, $lockedDependencies);
        } else {
            echo "whippet.json contains no dependencies\n";
            return \Result\Result::ok();
        }
    }

    private function update(array $dependencies, array $lockedDependencies)
    {
        $gitignore = $this->loadGitignore();
        $ignores = $gitignore->get_ignores();
        $ignores = $this->deleteDepsFromIgnores($ignores, $lockedDependencies);

        $this->updateHash();
        foreach ($dependencies as $dep) {
            echo sprintf("[Updating %s/%s]\n", $dep->type(), $dep->name());
            $result = $this->addDependencyToLockfile($dep);
            if ($result->isErr()) {
                return $result;
            }
            $ignores[] = $this->getGitignoreDependencyLine($dep->type(), $dep->name());
        }

        $gitignore->save_ignores(array_unique($ignores));
        $this->lockFile->saveToPath($this->dir.'/whippet.lock');

        return \Result\Result::ok();
    }

    private function saveChanges($dependencies)
    {
        $this->lockFile->saveToPath($this->dir.'/whippet.lock');
        $this->createGitIgnore($dependencies);
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
        return $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', (string) $this->dir);
    }

    private function deleteDepsFromIgnores($ignores, $lockedDependencies)
    {
        // Iterate through locked dependencies and remove from gitignore
        foreach ($lockedDependencies as $dep) {
            $line = $this->getGitignoreDependencyLine($dep['type'], $dep['name']);
            $index = array_search($line, $ignores);
            if ($index !== false) {
                unset($ignores[$index]);
            }
        }

        return $ignores;
    }

    private function getLockedDependencies($lockFile)
    {
        $lockDependencies = [];
        foreach (['themes', 'plugins'] as $type) {
            $typeDependencies = $lockFile->getDependencies($type);
            $typeDependencies = array_map(function ($typeDep) use ($type) {
                $typeDep['type'] = $type;
                return $typeDep;
            }, $typeDependencies);

            $lockDependencies = array_merge($lockDependencies, $typeDependencies);
        }
        return $lockDependencies;
    }

    private function getGitignoreDependencyLine($type, $name)
    {
        return '/wp-content/'.$type.'/'.$name."\n";
    }

    private function addDependencyToLockfile($dependency)
    {
        $src = $dependency->src();
        $ref = $dependency->ref();

        if (!isset($src)) {
            return \Result\Result::err('missing sources');
        }

        $commitResult = $this->fetchLatestRevisionData($src, $ref);

        if ($commitResult->isErr()) {
            return $commitResult;
        }

        $this->lockFile->addDependency($dependency->type(), $dependency->name(), $src, $commitResult->unwrap());

        return \Result\Result::ok();
    }

    private function fetchLatestRevisionData($src, $ref)
    {
        $result = $this->factory->callStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', $src, $ref);

        if ($result->isErr()) {
            return \Result\Result::err(sprintf('git command failed: %s', $result->getErr()));
        }

        return $result;
    }
}
