<?php

namespace Dxw\Whippet\Modules;

class Generate
{
	private $generators_dir;

	public function __construct()
	{
		$this->generators_dir = WHIPPET_ROOT.'/generators';
	}

	public function start($thing, $options)
	{
		if ($thing) {
			$this->generate($thing, $options);
		} else {
			if (isset($options->list)) {
				$this->list_generators();
			}
		}
	}

	public function generate($thing, $options)
	{
		$generator_file = "{$this->generators_dir}/{$thing}/generate.php";

		if (!file_exists($generator_file)) {
			echo "Could not find a generator for {$thing}\n";
			exit(1);
		}

		require $generator_file;
		$generator_class = ucfirst($thing).'Generator';

		return (new $generator_class($options))->generate();
	}

	public function list_generators()
	{
		echo "Available generators:\n\n";
		foreach (array_keys($this->get_generators()) as $generator) {
			echo "  $generator\n";
		}
	}

	public function get_generators()
	{
		$generators = [];

		foreach (new \DirectoryIterator($this->generators_dir) as $file) {
			if ($file->isDot()) {
				continue;
			}

			if ($file->isDir()) {
				$generator_file = $this->generators_dir.'/'.$file->getFilename().'/generate.php';

				if (file_exists($generator_file)) {
					$generators[ucfirst($file->getFilename())] = $generator_file;
				}
			}
		}

		return $generators;
	}
};
