<?php

trait Helpers
{
	private $factoryNewInstance;
	private $factoryCallStatic;

	public function setUp(): void
	{
		$this->factoryNewInstance = [];
		$this->factoryCallStatic = [];
	}

	private function getWhippetLock(/* string */ $hash, array $dependencyMap)
	{
		$whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\Files\\WhippetLock')
		->disableOriginalConstructor()
		->getMock();

		$whippetLock->method('getHash')
		->willReturn($hash);

		$map = [];
		foreach ($dependencyMap as $dependencyType => $return) {
			$map[] = [$dependencyType, $return];
		}

		$whippetLock->method('getDependencies')
		->will($this->returnValueMap($map));

		return $whippetLock;
	}

	private function getArchivedWarning()
	{
		$warning = <<<'EOT'
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!! WARNING: GitHub repo is archived. This dependency !!
!! should be replaced before the repo is removed.    !!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

EOT;
		return $warning;
	}

	private function getGit($isRepo, $cloneRepo, $checkout, $isArchived = false)
	{
		$archived_warning = $this->getArchivedWarning();

		$git = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Git')
		->disableOriginalConstructor()
		->getMock();

		$git->method('is_repo')
		->willReturn($isRepo);

		if ($cloneRepo !== null) {
			$return = true;
			$output = "git clone output\n";

			if ($isArchived) {
				$output = $archived_warning . $output;
			}

			if (is_array($cloneRepo)) {
				$return = $cloneRepo['return'];
				$cloneRepo = $cloneRepo['with'];
			}

			$git->expects($this->exactly(1))
			->method('clone_repo')
			->with($cloneRepo)
			->will($this->returnCallback(function () use ($output, $return) {
				echo $output;

				return $return;
			}));
		}

		if ($checkout !== null) {
			$return = true;
			$output = "git checkout output\n";

			if ($isArchived) {
				$output = $archived_warning . $output;
			}

			if (is_array($checkout)) {
				$return = $checkout['return'];
				$checkout = $checkout['with'];
			}

			$git->expects($this->exactly(1))
			->method('checkout')
			->with($checkout)
			->will($this->returnCallback(function () use ($output, $return) {
				echo $output;

				return $return;
			}));
		}

		return $git;
	}

	private function getWhippetJson(array $data)
	{
		return new \Dxw\Whippet\Files\WhippetJson($data);
	}

	private function getFactory()
	{
		$factory = $this->getMockBuilder('\\Dxw\\Whippet\\Factory')
		->disableOriginalConstructor()
		->getMock();

		$factory->method('newInstance')
		->will($this->returnValueMap($this->factoryNewInstance));

		$factory->method('callStatic')
		->will($this->returnValueMap($this->factoryCallStatic));

		return $factory;
	}

	private function addFactoryNewInstance()
	{
		$this->factoryNewInstance[] = func_get_args();
	}

	private function addFactoryCallStatic()
	{
		$this->factoryCallStatic[] = func_get_args();
	}

	private function getProjectDirectory($dir)
	{
		return new \Dxw\Whippet\ProjectDirectory($dir);
	}

	private function getDir()
	{
		$root = \org\bovigo\vfs\vfsStream::setup();

		return $root->url();
	}
}
