<?php

namespace Dxw\Whippet\Dependencies;

class Describer
{
	private $lockFile;
	private $dir;
	private $factory;

	public function __construct(
		\Dxw\Whippet\Factory $factory,
		\Dxw\Whippet\ProjectDirectory $dir
	) {
		$this->factory = $factory;
		$this->dir = $dir;
	}

	public function fetchVersionInformation()
	{
		$resultLoad = $this->loadWhippetLock();
		if ($resultLoad->isErr()) {
			return $resultLoad;
		}
		$git = new \Dxw\Whippet\Git\Git($this->dir);
		$results = [];
		foreach (DependencyTypes::getThemeAndPluginTypes() as $type) {
			foreach ($this->lockFile->getDependencies($type) as $dep) {
				$result = $git::tag_for_commit($dep['src'], $dep['revision']);
				if ($result->isErr()) {
					return $result;
				}
				$results[$type][$dep["name"]] = $result->unwrap();
			}
		}
		return \Result\Result::ok($results);
	}

	public function describe()
	{
		$results = $this->fetchVersionInformation();
		if ($results->isErr()) {
			return $results;
		}
		$pretty_results = json_encode($results->unwrap(), JSON_PRETTY_PRINT);
		printf($pretty_results);

		return \Result\Result::ok();
	}

	private function loadWhippetLock()
	{
		$result = $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
		if ($result->isErr()) {
			return $result;
		} else {
			$this->lockFile = $result->unwrap();
		}

		return \Result\Result::ok();
	}
}
