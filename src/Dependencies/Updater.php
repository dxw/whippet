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

        $sources = $this->jsonFile->getSources();
        return $this->update([$type=>[$dep]], $sources);
    }

    public function updateAll()
    {
        $result = $this->loadWhippetFiles();
        if ($result->isErr()) {
            return $result;
        }

        $allDependencies = array();


        foreach (['themes', 'plugins'] as $type) {
            $allDependencies[$type] = $this->jsonFile->getDependencies($type);
        }

        $sources = $this->jsonFile->getSources();
        return $this->update($allDependencies, $sources);
    }

    private function update(array $dependencies, array $sources)
    {
        $this->updateHash();
        $this->loadGitignore();
        $count = 0;
        foreach ($dependencies as $type => $typeDependencies) {
            foreach ($typeDependencies as $dep) {
                echo sprintf("[Updating %s/%s]\n", $type, $dep['name']);
                $result = $this->addDependencyToLockfile($type, $dep, $sources);
                if ($result->isErr()) {
                    return $result;
                }
                ++$count;
            }
        }
        $this->saveChanges();

        if ($count === 0) {
            echo "whippet.json contains no dependencies\n";
        }
        return \Result\Result::ok();
    }

    private function saveChanges()
    {
        $this->lockFile->saveToPath($this->dir.'/whippet.lock');
        $this->createGitIgnore();
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

    private function createGitIgnore()
    {
        foreach (['themes', 'plugins'] as $type) {
            foreach ($this->jsonFile->getDependencies($type) as $dep) {
                $this->addDependencyToIgnoresArray($type, $dep['name']);
            }
        }
        $this->gitignore->save_ignores(array_unique($this->ignores));
    }

    private function loadGitignore()
    {
        $this->gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', (string) $this->dir);

        $this->ignores = $this->gitignore->get_ignores();

        // Iterate through locked dependencies and remove from gitignore
        $lockedDependencies = $this->getLockedDependencies();
        foreach ($lockedDependencies as $dep) {
            $line = $this->getGitignoreDependencyLine($dep['type'], $dep['name']);
            $index = array_search($line, $this->ignores);
            if ($index !== false) {
                unset($this->ignores[$index]);
            }
        }
    }

    private function getLockedDependencies()
    {
        $lockDependencies = [];
        foreach (['themes', 'plugins'] as $type) {
            $typeDependencies = $this->lockFile->getDependencies($type);
            $typeDependencies = array_map(function ($typeDep) use ($type) {
                $typeDep['type'] = $type;
                return $typeDep;
            }, $typeDependencies);

            $lockDependencies = array_merge($lockDependencies, $typeDependencies);
        }
        return $lockDependencies;
    }

    private function addDependencyToIgnoresArray($type, $name)
    {
        $this->ignores[] = $this->getGitignoreDependencyLine($type, $name);
    }

    private function getGitignoreDependencyLine($type, $name)
    {
        return '/wp-content/'.$type.'/'.$name."\n";
    }

    private function addDependencyToLockfile($type, array $dep, $sources)
    {
        if (isset($dep['src'])) {
            $src = $dep['src'];
        } else {
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
