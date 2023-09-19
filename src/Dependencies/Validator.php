<?php

namespace Dxw\Whippet\Dependencies;

class Validator
{
	private $factory;
	private $dir;

	public function __construct(
		\Dxw\Whippet\Factory $factory,
		\Dxw\Whippet\ProjectDirectory $dir
	) {
		$this->factory = $factory;
		$this->dir = $dir;
	}

	public function validate(bool $enforceRefs = false)
	{
		$whippetLock = $this->loadWhippetLock();
		if ($whippetLock->isErr()) {
			return \Result\Result::err(sprintf(
				'whippet.lock error: %s',
				$whippetLock->getErr()
			));
		} else {
			$whippetLock = $whippetLock->unwrap();
		}

		$whippetJson = $this->loadWhippetJson();
		if ($whippetJson->isErr()) {
			return \Result\Result::err(sprintf(
				'whippet.json error: %s',
				$whippetJson->getErr()
			));
		} else {
			$whippetJson = $whippetJson->unwrap();
		}

		// Check hashes
		if ($this->whippetJsonHash() !== $whippetLock->getHash()) {
			return \Result\Result::err(
				'hash mismatch between whippet.json and whippet.lock'
			);
		};

		// Check that entries in whippet.json
		// match entries in whippet.lock
		foreach (DependencyTypes::getDependencyTypes() as $type) {
			$whippetJsonDependencies = $whippetJson->getDependencies($type);
			$whippetLockDependencies = $whippetLock->getDependencies($type);
			if (count($whippetJsonDependencies) !== count($whippetLockDependencies)) {
				return \Result\Result::err(sprintf('Mismatched dependencies count for type %s', $type));
			}

			foreach ($whippetJsonDependencies as $whippetJsonDependency) {
				if (!$this->lockMatchFoundForDependency($whippetJsonDependency, $whippetLockDependencies)) {
					return \Result\Result::err(sprintf('No entry found in whippet.lock for %s: %s', $type, $whippetJsonDependency["name"]));
				}
				if ($enforceRefs) {
					if (!array_key_exists('ref', $whippetJsonDependency)) {
						return \Result\Result::err(sprintf("Missing reference in whippet.json for %s: %s", $type, $whippetJsonDependency["name"]));
					}
				}
			}

			foreach ($whippetLockDependencies as $whippetLockDependency) {
				foreach (['revision', 'src'] as $property) {
					if (!array_key_exists($property, $whippetLockDependency)) {
						return \Result\Result::err(sprintf("Missing %s property in whippet.lock for %s: %s", $property, $type, $whippetLockDependency["name"]));
					}
				}
			}
		}

		echo "Valid whippet.json and whippet.lock \n";

		return \Result\Result::ok();
	}

	private function loadWhippetLock()
	{
		return $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $this->dir.'/whippet.lock');
	}

	private function loadWhippetJson()
	{
		return $this->factory->callStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $this->dir.'/whippet.json');
	}

	private function whippetJsonHash()
	{
		$contents = file_get_contents($this->dir.'/whippet.json');

		// Strip CR for git/Windows compatibility
		$contents = strtr($contents, ["\r" => '']);

		return sha1($contents);
	}

	private function lockMatchFoundForDependency($whippetJsonDependency, $whippetLockDependencies)
	{
		$matchFound = false;
		foreach ($whippetLockDependencies as $whippetLockDependency) {
			if ($whippetJsonDependency["name"] == $whippetLockDependency["name"]) {
				$matchFound = true;
			}
		}
		return $matchFound;
	}
}
