<?php

namespace Dxw\Whippet\Dependencies;

class Updater
{
	private $factory;
	private $dir;
	private $lockFile;
	private $jsonFile;
	private $ignores;
	private $gitignore;

	public function __construct(
		\Dxw\Whippet\Factory $factory,
		\Dxw\Whippet\ProjectDirectory $dir
	) {
		$this->factory = $factory;
		$this->dir = $dir;
	}

	public function updateSingle($dep)
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

		$result = $this->prepareForUpdate();
		if ($result->isErr()) {
			return $result;
		}

		$dep = $this->jsonFile->getDependency($type, $name);
		if ($dep === []) {
			return \Result\Result::err('No matching dependency in whippet.json');
		}

		return $this->update([$type => [$dep]]);
	}

	public function updateAll()
	{
		$result = $this->prepareForUpdate();
		if ($result->isErr()) {
			return $result;
		}

		$allDependencies = [];

		foreach (['themes', 'plugins'] as $type) {
			$allDependencies[$type] = $this->jsonFile->getDependencies($type);
		}

		return $this->update($allDependencies);
	}

	private function update(array $dependencies)
	{
		$this->updateHash();
		$this->loadGitignore();
		$count = 0;
		foreach ($dependencies as $type => $typeDependencies) {
			foreach ($typeDependencies as $dep) {
				echo sprintf("[Updating %s/%s]\n", $type, $dep['name']);
				$result = $this->addDependencyToLockfile($type, $dep);
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

	private function prepareForUpdate()
	{
		$result = $this->loadWhippetFiles();
		if ($result->isErr()) {
			return $result;
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
		$contents = file_get_contents($this->dir.'/whippet.json');

		// Strip CR for git/Windows compatibility
		$contents = strtr($contents, ["\r" => '']);

		$jsonHash = sha1($contents);
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

	private function addDependencyToIgnoresArray($type, $name)
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

		if (isset($dep['ref'])) {
			$ref = $dep['ref'];
			$commitResult = $this->fetchRef($src, $ref);
		} else {
			$commitResult = $this->fetchDefault($src);
		}

		if ($commitResult->isErr()) {
			return \Result\Result::err(sprintf('git command failed: %s', $commitResult->getErr()));
		}

		$this->lockFile->addDependency($type, $dep['name'], $src, $commitResult->unwrap());

		return \Result\Result::ok();
	}

	private function fetchRef(string $src, string $ref): \Result\Result
	{
		return $this->factory->callStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', $src, $ref);
	}

	// Fetch the default branch
	// That may be `main` or `master`
	private function fetchDefault(string $src): \Result\Result
	{
		$main = $this->fetchRef($src, 'main');

		if (!$main->isErr()) {
			return $main;
		}

		return $this->fetchRef($src, 'master');
	}
}
