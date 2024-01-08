<?php

namespace Dxw\Whippet\Modules\Helpers;

trait WhippetHelpers
{
	private $plugins_manifest_file;
	private $project_dir;
	private $application_config;
	private $plugins_lock_file;

	public function check_and_create_dir($dir, $force_empty = false)
	{
		if (!file_exists($dir)) {
			if (!mkdir($dir)) {
				throw new \Exception("Unable to create directory: {$dir}");
			}
		} elseif ($force_empty) {
			if ((new \FilesystemIterator($dir))->valid()) {
				throw new \Exception("Directory exists but is not empty: {$dir}");
			}
		}
	}

	public function whippet_init()
	{
		if (!$this->plugins_manifest_file = $this->find_file('plugins')) {
			if (!$this->plugins_manifest_file = $this->find_file('Plugins')) {
				if (!$this->plugins_manifest_file = $this->find_file('whippet.json')) {
					echo "Unable to find whippet.json or plugins manifest file\n";
					exit(1);
				}
			}
		}
		$this->project_dir = dirname($this->plugins_manifest_file);

		$this->check_for_missing_whippet_files($this->project_dir);

		$this->plugins_lock_file = $this->find_file('plugins.lock');
		$this->plugin_dir = "{$this->project_dir}/wp-content/plugins";

		$this->load_application_config();
	}

	public function load_application_config()
	{
		$application_config_file = "{$this->project_dir}/config/application.json";

		if (file_exists($application_config_file)) {
			$this->application_config = json_decode(file_get_contents($application_config_file));

			if (!is_object($this->application_config)) {
				echo 'Unable to parse application config';
				exit(1);
			}
		} else {
			$this->application_config = json_decode('
            {
                "wordpress": {
                    "repository": "git@git.govpress.com:wordpress/snapshot",
                    "revision": "master"
                }
            }
            ');

			if (file_put_contents($application_config_file, json_encode($this->application_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
				echo "A default application.json was created\n";
			} else {
				echo "No config/application.json was found, and no default could be created. Quitting.\n";
				exit(1);
			}
		}
	}

	/**
	 * Version strings passed to WordPress APIs must not start with a v.
	 *
	 * So, v6.3.1 becomes 6.3.1.
	 */
	public function get_bare_version_number($version)
	{
		if(substr($version, 0, 1) === 'v') {
			return substr($version, 1);
		}
		return $version;
	}

	/** Given a version in config/application.json, return a valid WP Core version.
	 *
	 * Note that version numbers in repos can be of the following forms:
	 *
	 * v6
	 * 6.3.2
	 * 6.3
	 * 6
	 *
	 * and should be translated into a full semver version, e.g. 6.3.2.
	 */
	public function get_wordpress_core_version()
	{
		$config_version = $this->application_config->wordpress->revision;
		if (substr_count($config_version, '.') === 2) {
			return $config_version;
		}
		if ($config_version == 'master') {
			$latest_core_version = json_decode(file_get_contents('https://api.wordpress.org/core/version-check/1.7/'), JSON_OBJECT_AS_ARRAY);
			return $latest_core_version['offers'][0]['version'];
		}
		// Version does not include a patch version, so we need to find the
		// fully qualified version humber from the JSON. We assume that the
		// JSON data is ordered by most recent release first.
		$latest_core_version = json_decode(file_get_contents('https://api.wordpress.org/core/version-check/1.7/'), JSON_OBJECT_AS_ARRAY);
		$i = 0;
		$canonical_version = $this->get_bare_version_number($config_version);
		$ver_len = strlen($canonical_version);
		while ($i < count($latest_core_version['offers'])) {
			print_r("counter: {$i} canonical version: {$canonical_version}\n");
			if (substr($latest_core_version['offers'][$i]['version'], 0, $ver_len) === $canonical_version) {
				print_r("VERSION: {$latest_core_version['offers'][$i]['version']}\n");
				return $latest_core_version['offers'][$i]['version'];
			}
			$i++;
		}
		throw new \Exception("WordPress core version not valid or no longer available: {$config_version}");
	}

	public function find_file($file, $include_dir = false)
	{
		// Starting in the current dir, walk up until we find the file
		$path = getcwd();

		do {
			$file_path = $path.'/'.$file;

			if (file_exists($file_path) && ($include_dir || is_file($file_path))) {
				return $file_path;
			}

			$path = dirname($path);
		} while ($path !== '.' && $path !== '/'); // dirname returns . or / if you call it on /, depending on platform

		return false;
	}

	// 77.079482% credit:
	// gimmicklessgpt@gmail.com
	// http://php.net/manual/en/function.copy.php
	// Modified to copy symlinks
	public function recurse_copy($src, $dst)
	{
		$dir = opendir($src);
		if (!is_dir($dst) && !is_link($dst)) {
			mkdir($dst);
		}
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_link($src.'/'.$file)) {
					symlink(readlink($src.'/'.$file), $dst.'/'.$file);
				} elseif (is_dir($src.'/'.$file)) {
					$this->recurse_copy($src.'/'.$file, $dst.'/'.$file);
				} else {
					copy($src.'/'.$file, $dst.'/'.$file);
				}
			}
		}
		closedir($dir);
	}

	// 100% credit:
	// The suckiness of PHP
	public function recurse_rmdir($dir)
	{
		$dir_handle = opendir($dir);
		while (false !== ($file = readdir($dir_handle))) {
			if (($file != '.') && ($file != '..')) {
				if (is_link($dir.'/'.$file)) {
					unlink($dir.'/'.$file);
				} elseif (is_dir($dir.'/'.$file)) {
					$this->recurse_rmdir($dir.'/'.$file);
				} else {
					unlink($dir.'/'.$file);
				}
			}
		}
		rmdir($dir);
	}

	public function recurse_rm($path)
	{
		if (!file_exists($path)) {
			return;
		}

		if (is_dir($path)) {
			$this->recurse_rmdir($path);
		} else {
			unlink($path);
		}
	}

	private function check_for_missing_whippet_files($project_dir)
	{
		$whippet_files = [
			'config/',
			'wp-content/',
			'wp-content/plugins/',
			'.gitignore',
		];

		$missing = [];
		foreach ($whippet_files as $file) {
			if (!file_exists("{$project_dir}/{$file}")) {
				$missing[] = $file;
			}
		}

		if (count($missing) > 0) {
			echo "The following files and directories are required but could not be found:\n";
			foreach ($missing as $file) {
				echo "  {$file}\n";
			}
			exit(1);
		}
	}

	public function find_and_replace($dir, $find, $replaceWith)
	{
		$files = $this->recurse_file_search($dir);
		foreach ($files as $filename) {
			if (is_file($filename) && is_writable($filename)) {
				$file = file_get_contents($filename);
				file_put_contents($filename, str_replace($find, $replaceWith, $file));
			}
		}
	}

	public function recurse_file_search($dir)
	{
		$recursive_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
		$files = [];
		foreach ($recursive_iterator as $file) {
			if ($file->isDir()) {
				continue;
			}
			$files[] = $file->getPathname();
		}
		return $files;
	}

	public function download_url_to_file($url, $dest)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		$file = fopen($dest, "w+");
		fputs($file, $data);
		fclose($file);
	}

	public function unzip_to_folder($zip_file, $dest)
	{
		$zip = new \ZipArchive();
		$res = $zip->open($zip_file);
		if ($res === true) {
			$zip->extractTo($dest); // directory to extract contents to
			$zip->close();
			unlink($zip_file);
		} else {
			echo "Unzip failed \n, error code: " . $res;
		}
	}
};
