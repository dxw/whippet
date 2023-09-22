<?php

namespace Dxw\Whippet\Dependencies;

class Installer
{
	private $factory;
	private $dir;
	private $inspectionChecker;
	private $lockFile;

	public function __construct(
		\Dxw\Whippet\Factory $factory,
		\Dxw\Whippet\ProjectDirectory $dir,
		$inspection_checker
	) {
		$this->factory = $factory;
		$this->dir = $dir;
		$this->inspectionChecker = $inspection_checker;
	}

	public function installAll()
	{
		$result = $this->loadWhippetFiles();
		if ($result->isErr()) {
			return $result;
		}

		$dependencies = [];

		foreach (DependencyTypes::getThemeAndPluginTypes() as $type) {
			$dependencies[$type] = $this->lockFile->getDependencies($type);
		}

		return $this->install($dependencies);
	}

	public function installSingle($dep)
	{
		//Will only get here if $dep is valid format and matches an entry in whippet.json

		$result = $this->loadWhippetFiles();
		if ($result->isErr()) {
			return $result;
		}

		$type = explode('/', $dep)[0];
		$name = explode('/', $dep)[1];

		foreach ($this->lockFile->getDependencies($type) as $dep) {
			if ($dep['name'] === $name || (DependencyTypes::isLanguageType($type) && substr($dep['name'], 0, strlen($name)) === $name)) {
				$result = $this->install([$type => [$dep]]);
				if ($result->isErr()) {
					return $result;
				}
			}
		}

		return \Result\Result::ok();
	}

	private function install(array $dependencies)
	{
		$count = 0;

		foreach ($dependencies as $type => $typeDependencies) {
			foreach ($typeDependencies as $dependency) {
				$result = $this->installDependency($type, $dependency);
				if ($result->isErr()) {
					return $result;
				}

				echo $this->inspectionDetailsMessage($type, $dependency);
				echo "\n";

				++$count;
			}
		}

		if ($count === 0) {
			echo "whippet.lock contains nothing to install\n";
		}

		return \Result\Result::ok();
	}

	private function loadWhippetFiles()
	{
		if (!is_file($this->dir.'/whippet.json')) {
			return \Result\Result::err('whippet.json not found');
		}

		$result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
		if ($result->isErr()) {
			return \Result\Result::err(sprintf('whippet.lock: %s', $result->getErr()));
		}
		$this->lockFile = $result->unwrap();

		$contents = file_get_contents($this->dir.'/whippet.json');

		// Strip CR for git/Windows compatibility
		$contents = strtr($contents, ["\r" => '']);

		$hash = sha1($contents);
		if ($this->lockFile->getHash() !== $hash) {
			return \Result\Result::err('mismatched hash - run `whippet dependencies update` first');
		}

		return \Result\Result::ok();
	}

	private function installDependency($type, $dep)
	{
		if (DependencyTypes::isLanguageType($type)) {
			return $this->installLanguageDependency($dep);
		}
		return $this->installThemeOrPluginDependency($type, $dep);
	}

	/**
	 * Download, unzip and install a language pack for core, a plugin or a theme.
	 *
	 * $dep['name'] will be a language code for WP core, or this format:
	 *     <lang-code>/<dependency type>/<slug>
	 * e.g. "en_GB/plugins/classic-editor".
	 */
	private function installLanguageDependency($dep)
	{
		$type = strpos($dep['name'], '/') !== false ? explode('/', $dep['name'])[1] : '';
		$slug = strpos($dep['name'], '/') !== false ? explode('/', $dep['name'])[2] : '';
		$lang = explode('/', $dep['name'])[0];

		$for_dep = strlen($type) > 0 ? " for {$type}/{$slug}" : '';
		echo sprintf("[Adding languages/%s%s]\n", $lang, $for_dep);

		$dir_s = strval($this->dir);
		$path = strlen($type) > 0 ? "{$dir_s}/wp-content/languages/{$type}" : "{$dir_s}/wp-content/languages";

		try {
			$zipFileContents = file_get_contents($dep['src']);
		} catch (\Exception $exn) {
			return \Result\Result::err("got error: {$exn->getMessage()} on downloading: {$dep['src']}");
		}
		if ($zipFileContents === false) {
			return \Result\Result::err("could not download {$dep['src']} or file was empty");
		}

		$tempFile = tempnam(sys_get_temp_dir(), 'temp_zip');
		file_put_contents($tempFile, $zipFileContents);

		$zip = new \ZipArchive();
		if ($zip->open($tempFile) === true) {
			$zip->extractTo($path);
			$zip->close();
		} else {
			return \Result\Result::err("could not unzip file from: {$dep['src']}}");
		}

		unlink($tempFile);

		return \Result\Result::ok();
	}

	private function installThemeOrPluginDependency($type, $dep)
	{
		$path = $this->dir.'/wp-content/'.$type.'/'.$dep['name'];

		$git = $this->factory->newInstance('\\Dxw\\Whippet\\Git\\Git', $path);

		if (!$git->is_repo()) {
			echo sprintf("[Adding %s/%s]\n", $type, $dep['name']);
			$result = $git->clone_repo($dep['src']);

			if ($result === false) {
				return \Result\Result::err('could not clone repository');
			}
		} else {
			echo sprintf("[Checking %s/%s]\n", $type, $dep['name']);
		}

		$result = $git->checkout($dep['revision']);
		if ($result === false) {
			return \Result\Result::err('could not checkout revision');
		}

		return \Result\Result::ok();
	}

	private function inspectionDetailsMessage($type, $dep)
	{
		$result = $this->inspectionChecker->check($type, $dep);

		if (!$result->isErr()) {
			$inspectionDetails = $result->unwrap();
			if (!empty($inspectionDetails)) {
				$message = sprintf("%s\n", $inspectionDetails);
			} else {
				$message = null;
			}
		} else {
			$error = $result->getErr();
			$message = sprintf("[ERROR] %s\n", $error);
		}
		return $message;
	}
}
