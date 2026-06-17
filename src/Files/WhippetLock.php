<?php

namespace Dxw\Whippet\Files;

/**
 * @psalm-suppress UnusedClass
 */
class WhippetLock extends Base
{
	public function getDependencies(/* string */ $type)
	{
		if (!isset($this->data[$type])) {
			return [];
		}

		return $this->data[$type];
	}

	public function getHash()
	{
		return $this->data['hash'];
	}

	public function setHash(/* string */ $hash)
	{
		$this->data['hash'] = $hash;
	}

	public function addDependency(/* string */ $type, /* string */ $name, /* string */ $src, /* string */ $revision)
	{
		if (isset($this->data[$type])) {
			foreach ($this->data[$type] as $key => $dependency) {
				if ($name === $dependency['name']) {
					array_splice($this->data[$type], $key, 1);
				}
			}
		}

		$this->data[$type][] = [
			'name' => $name,
			'src' => $src,
			'revision' => $revision,
		];
	}

	public function saveToPath($path)
	{
		foreach (\Dxw\Whippet\Dependencies\DependencyTypes::getDependencyTypes() as $type) {
			if (isset($this->data[$type])) {
				usort($this->data[$type], function ($item1, $item2) {
					return $item1['name'] <=> $item2['name'];
				});
			}
		}
		parent::saveToPath($path);
	}
}
