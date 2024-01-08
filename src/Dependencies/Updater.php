<?php

namespace Dxw\Whippet\Dependencies;

use Dxw\Whippet\Models\TranslationsApi;

class Updater
{
	use \Dxw\Whippet\Modules\Helpers\WhippetHelpers;
	private $factory;
	private $lockFile;
	private $jsonFile;
	private $ignores;
	private $gitignore;
	private $describer;
	// To update language packs we need version numbers for themes and plugins,
	// hashes cannot be used with the APIs.
	private $themeAndPluginVersions;

	public function __construct(
		\Dxw\Whippet\Factory $factory,
		\Dxw\Whippet\ProjectDirectory $dir
	) {
		$this->factory = $factory;
		$this->project_dir = $dir;  // needed by methods from WhippetHelpers.
		$this->describer = new Describer($factory, $dir);
		$this->load_application_config();
	}

	public function updateSingle($dep)
	{
		$result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->project_dir.'/whippet.lock');
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

		// If the single dependency is a language, we need access to version
		// information about the existing plugins and themes in the lockfile.
		if (DependencyTypes::isLanguageType($type)) {
			$result = $this->getPluginAndThemeVersions(true);
			if ($result->isErr()) {
				return $result;
			}
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

		// Because we want to update language packs for themes and plugins,
		// we need to update those dependencies first, so we can find version
		// numbers for language packs associated with each theme/plugin.
		$themeAndPluginDependencies = [];

		foreach (DependencyTypes::getThemeAndPluginTypes() as $type) {
			$themeAndPluginDependencies[$type] = $this->jsonFile->getDependencies($type);
		}

		$result = $this->getPluginAndThemeVersions(false);
		if ($result->isErr()) {
			return $result;
		}

		$allDependencies = $themeAndPluginDependencies;
		$allDependencies[DependencyTypes::LANGUAGES] = $this->jsonFile->getDependencies(DependencyTypes::LANGUAGES);

		$result = $this->update($allDependencies);
		if ($result->isErr()) {
			return $result;
		}
		return \Result\Result::ok();
	}

	private function getPluginAndThemeVersions($fromLockfile = false)
	{
		$result = $fromLockfile ? $this->describer->fetchVersionInformation() : $this->describer->fetchVersionInformation($this->lockFile);
		if ($result->isErr()) {
			return $result;
		}
		$versions = $result->unwrap();
		if (array_key_exists(DependencyTypes::LANGUAGES, $versions)) {
			unset($versions[DependencyTypes::LANGUAGES]);
		}
		$this->themeAndPluginVersions = $versions;
		return \Result\Result::ok();
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
		$this->lockFile->saveToPath($this->project_dir.'/whippet.lock');
		$this->createGitIgnore();
	}

	private function loadWhippetFiles()
	{
		$result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $this->project_dir.'/whippet.json');
		if ($result->isErr()) {
			return \Result\Result::err(sprintf('whippet.json: %s', $result->getErr()));
		}
		$this->jsonFile = $result->unwrap();

		$result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->project_dir.'/whippet.lock');
		if ($result->isErr()) {
			$this->lockFile = $this->factory->newInstance('\\Dxw\\Whippet\\Files\\WhippetLock', []);
		} else {
			$this->lockFile = $result->unwrap();
		}

		return \Result\Result::ok();
	}

	private function updateHash()
	{
		$contents = file_get_contents($this->project_dir.'/whippet.json');

		// Strip CR for git/Windows compatibility
		$contents = strtr($contents, ["\r" => '']);

		$jsonHash = sha1($contents);
		$this->lockFile->setHash($jsonHash);
	}

	private function createGitIgnore()
	{
		foreach (DependencyTypes::getDependencyTypes() as $type) {
			foreach ($this->jsonFile->getDependencies($type) as $dep) {
				$this->addDependencyToIgnoresArray($type, $dep['name']);
			}
		}
		$this->gitignore->save_ignores(array_unique($this->ignores));
	}

	private function loadGitignore()
	{
		$this->gitignore = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Gitignore', (string) $this->project_dir);

		$this->ignores = [];
		if (is_file($this->project_dir.'/.gitignore')) {
			$this->ignores = $this->gitignore->get_ignores();
		}

		// Iterate through locked dependencies and remove from gitignore
		foreach (DependencyTypes::getDependencyTypes() as $type) {
			foreach ($this->lockFile->getDependencies($type) as $dep) {
				$line = $this->getGitignoreDependencyLine($type, $dep['name']);
				$index = array_search($line, $this->ignores);
				if ($index !== false) {
					unset($this->ignores[$index]);
				}
			}
		}
		$this->ignores = array_values($this->ignores);
	}

	private function addDependencyToIgnoresArray($type, $name)
	{
		$this->ignores[] = $this->getGitignoreDependencyLine($type, $name);
	}

	private function getGitignoreDependencyLine($type, $name)
	{
		if (DependencyTypes::isLanguageType($type)) {
			// Plugin language packs go in /wp-content/languages/plugins/
			return '/wp-content/'.$type."\n";
		}
		return '/wp-content/'.$type.'/'.$name."\n";
	}

	private function addDependencyToLockfile($type, array $dep)
	{
		if (DependencyTypes::isLanguageType($type)) {
			return $this->addLanguageToLockfile($dep);
		}
		return $this->addThemeOrPluginToLockfile($type, $dep);
	}

	private function addThemeOrPluginToLockfile($type, array $dep)
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

	/**
	 * Add a language pack to a lockfile, including translations for themes and plugins.
	 *
	 * Note that a missing language pack for WordPress Core is considered an
	 * error here, but a missing translation for a plugin or theme is not.
	 *
	 * The WordPress APIs do not expect versions to start with a v, i.e. if a
	 * revision number is 'v1.2.3' we pass in '1.2.3'.
	 */
	private function addLanguageToLockfile(array $dep)
	{
		$wpCoreVersion = $this->get_bare_version_number($this->get_wordpress_core_version());
		$result = TranslationsApi::fetchLanguageSrcAndRevision(DependencyTypes::LANGUAGES, $dep['name'], $wpCoreVersion, null);
		if ($result->isErr()) {
			return $result;
		}
		list($src, $revision) = $result->unwrap();
		if (is_null($src)) {
			return \Result\Result::err("{$dep['name']} is not available for WordPress Core.");
		}
		$this->lockFile->addDependency(DependencyTypes::LANGUAGES, $dep['name'], $src, $revision);

		foreach($this->themeAndPluginVersions as $type => $versions) {
			foreach($versions as $name => $version) {
				if(substr($version, 0, 18) === 'No tags for commit') {
					continue;
				} else {
					$version = $this->get_bare_version_number($version);
				}
				$result = TranslationsApi::fetchLanguageSrcAndRevision($type, $dep['name'], $version, $name);
				if ($result->isErr()) {
					echo sprintf("* Error encountered in updating {$dep['name']} translations for {$name} {$version}.\n");
					echo sprintf("    {$result->getErr()}\n");
					continue;
				}
				list($src, $revision) = $result->unwrap();
				if (is_null($src)) {
					echo sprintf("* No {$dep['name']} language pack available for {$name} {$version}.\n");
				} else {
					$translation = $dep['name'].'/'.$type.'/'.$name;
					$this->lockFile->addDependency(DependencyTypes::LANGUAGES, $translation, $src, $revision);
				}
			}
		}

		return \Result\Result::ok();
	}
}
