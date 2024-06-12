<?php

class Dependencies_Updater_Test extends \PHPUnit\Framework\TestCase
{
	use \Helpers;

	private function getGitignore(array $get, array $save, /* bool */ $saveIgnores, /* bool */ $warnOnGet)
	{
		$gitignore = $this->getMockBuilder('\\Dxw\\Whippet\\Git\\Gitignore')
		->disableOriginalConstructor()
		->getMock();

		$getIgnores = $gitignore->method('get_ignores');
		if ($warnOnGet) {
			$getIgnores->will($this->returnCallback(function () {
				trigger_error('$warnOnGet set but not prevented', E_USER_WARNING);
			}));
		} else {
			$getIgnores->willReturn($get);
		}

		$gitignore->expects($this->exactly($saveIgnores ? 1 : 0))
		->method('save_ignores')
		->with($save);

		return $gitignore;
	}

	private function getWhippetLockWritable(array $addDependency, /* string */ $hash, /* string */ $path, array $getDependencies, /* boolean */ $setHash = true)
	{
		$whippetLock = $this->getMockBuilder('\\Dxw\\Whippet\\Files\\WhippetLock')
		->disableOriginalConstructor()
		->getMock();

		call_user_func_array(
			[
				$whippetLock->expects($this->exactly(count($addDependency)))
				->method('addDependency'),
				'withConsecutive',
			],
			$addDependency
		);

		$whippetLock->expects($this->exactly($setHash === true ? 1 : 0))
		->method('setHash')
		->with($hash);

		$whippetLock->expects($this->exactly($path === null ? 0 : 1))
		->method('saveToPath')
		->with($path);

		if ($getDependencies === []) {
			$getDependencies = [
				['themes', []],
				['plugins', []],
				['languages', []],
			];
		}

		$whippetLock->method('getDependencies')
		->will($this->returnValueMap($getDependencies));

		return $whippetLock;
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testUpdateAll()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [
				[ 'name' => 'en_GB' ],
				[ 'name' => 'pt_BR' ],
			],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
			"/wp-content/languages\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
			['plugins', 'my-plugin', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'd961c3d'],
			['languages', 'en_GB', 'https://example.com/translation/core/6.3.1/en_GB.zip', '6.3.1'],
			['languages', 'en_GB/plugins/my-plugin', 'https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip', '1.6'],
			['languages', 'pt_BR', 'https://example.com/translation/core/6.3.1/pt_BR.zip', '6.3.1'],
			['languages', 'pt_BR/themes/my-theme', 'https://example.com/translation/theme/my-theme/1.4/en_GB.zip', '1.4'],
			['languages', 'pt_BR/plugins/my-plugin', 'https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip', '1.6'],
		], sha1('foobar'), $dir . '/whippet.lock', [
				[
					'themes', [
						[
							'name' => 'my-theme',
							'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
							'revision' => '27ba906',
						],
					],
				],
				[
					'plugins', [
						[
							'name' => 'my-plugin',
							'src' => 'git@git.govpress.com:wordpress-plugins/my-plugin',
							'revision' => 'd961c3d',
						],
					],
				],
				[
					'languages', [
						[
							'name' => 'en_GB',
							'src' => 'https://example.com/translation/core/6.3.1/en_GB.zip',
							'revision' => '6.3.1'
						],
						[
							'name' => 'en_GB',
							'src' => 'https://example.com/translation/core/6.3.1/en_GB.zip',
							'revision' => '6.3.1'
						],
						[
							'name' => 'en_GB',
							'src' => 'https://example.com/translation/core/6.3.1/en_GB.zip',
							'revision' => '6.3.1'
						],
						[
							'name' => 'pt_BR',
							'src' => 'https://example.com/translation/core/6.3.1/pt_BR.zip',
							'revision' => '6.3.1'
						],
						[
							'name' => 'pt_BR',
							'src' => 'https://example.com/translation/core/6.3.1/pt_BR.zip',
							'revision' => '6.3.1'
						],
					],
				],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'tag_for_commit', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906', \Result\Result::ok('v1.4'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'tag_for_commit', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'd961c3d', \Result\Result::ok('v1.6'));

		$fakeTranslationsApi = \Mockery::mock('alias:\\Dxw\\Whippet\\Models\\TranslationsApi');
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('languages', 'en_GB', '6.3.1', null)
		->andReturn(\Result\Result::ok(['https://example.com/translation/core/6.3.1/en_GB.zip', '6.3.1']));
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('languages', 'pt_BR', '6.3.1', null)
		->andReturn(\Result\Result::ok(['https://example.com/translation/core/6.3.1/pt_BR.zip', '6.3.1']));
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('plugins', 'en_GB', '1.6', 'my-plugin')
		->andReturn(\Result\Result::ok(['https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip', '1.6']));
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('themes', 'en_GB', '1.4', 'my-theme')
		->andReturn(\Result\Result::ok());
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('plugins', 'pt_BR', '1.6', 'my-plugin')
		->andReturn(\Result\Result::ok(['https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip', '1.6']));
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('themes', 'pt_BR', '1.4', 'my-theme')
		->andReturn(\Result\Result::ok(['https://example.com/translation/theme/my-theme/1.4/en_GB.zip', '1.4']));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals(
			"[Updating themes/my-theme]\n" .
			"[Updating plugins/my-plugin]\n" .
			"[Updating languages/en_GB]\n" .
			"* No en_GB language pack available for my-theme 1.4.\n" .
			"[Updating languages/pt_BR]\n",
			$output
		);

		\Mockery::close();
	}

	public function testUpdateAllWithExistingGitignore()
	{
		$dir = $this->getDirWithDefaults();
		touch($dir.'/.gitignore');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
		], [
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllWithExistingGitignoreNoDuplication()
	{
		$dir = $this->getDirWithDefaults();
		touch($dir.'/.gitignore');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
			"/wp-content/themes/my-theme\n",
		], [
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllFailedGitCommand()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
		], false, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::err('oh no'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals('git command failed: oh no', $result->getErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllWithExplicitSrc()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
					'src' => 'foobar',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'foobar', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'foobar', 'v1.4', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllWithoutRef()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'main', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllWithoutRefUsingMaster()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'main', \Result\Result::err('no such branch'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'master', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllBlankJsonfile()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("whippet.json contains no dependencies\n", $output);
	}

	public function testUpdateAllNoGitignore()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], true, true);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
			['plugins', 'my-plugin', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'd961c3d'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testUpdateAllRemoveFromGitignore()
	{
		$dir = $this->getDirWithDefaults();
		touch($dir.'/.gitignore');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/unmanaged-plugin\n",
			"/wp-content/plugins/removed-plugin\n",
		], [
			"/wp-content/plugins/unmanaged-plugin\n",
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', [
			[
				'themes', [
					[
						'name' => 'my-theme',
						'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
						'revision' => '27ba906',
					],
				],
			],
			[
				'plugins', [
					[
						'name' => 'removed-plugin',
						'src' => 'git@git.govpress.com:wordpress-plugins/removed-plugin',
						'revision' => 'd961c3d',
					],
				],
			],
			[
				'languages', [],
			],
		]);

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'tag_for_commit', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906', \Result\Result::ok('v1.4'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'tag_for_commit', 'git@git.govpress.com:wordpress-plugins/removed-plugin', 'd961c3d', \Result\Result::ok('1.6'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateAllBubbleErrors()
	{
		$dir = $this->getDirWithDefaults();

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::err('a WhippetJson error'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals('whippet.json: a WhippetJson error', $result->getErr());
		$this->assertEquals('', $output);
	}

	public function testUpdateAllNoExistingWhippetLock()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
			['plugins', 'my-plugin', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'd961c3d'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Files\\WhippetLock', [], $whippetLock);

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n[Updating plugins/my-plugin]\n", $output);
	}

	public function testUpdateAllWithBrokenJson()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], false, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateAll();
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals('missing sources', $result->getErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleWithNoLock()
	{
		$dir = $this->getDirWithDefaults();

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('twitget');
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals("No whippet.lock file exists, you need to run `whippet deps update` to generate one before you can update a specific dependency. \n", $output);
		$this->assertEquals('whippet.lock: file not found', $result->getErr());
	}

	public function testUpdateSingleIncorrectFormat()
	{
		$dir = $this->getDirWithDefaults();

		file_put_contents($dir.'/whippet.json', 'foobar');

		$whippetLock = [];
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('twitget');
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals("Dependency should be in format [type]/[name]. \n", $output);
		$this->assertEquals('Incorrect dependency format', $result->getErr());
	}

	public function testUpdateSingleNoMatch()
	{
		$dir = $this->getDirWithDefaults();
		file_put_contents($dir.'/whippet.json', 'foobar');
		$whippetJson = $this->getWhippetJson([
			'src' => [
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));


		$whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, [], false);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('plugins/twitget');
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals('No matching dependency in whippet.json', $result->getErr());
	}

	public function testUpdateSingleBrokenJson()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], false, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals('missing sources', $result->getErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleWithExistingGitignore()
	{
		$dir = $this->getDirWithDefaults();
		touch($dir.'/.gitignore');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
		], [
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleWithExistingGitignoreNoDuplication()
	{
		$dir = $this->getDirWithDefaults();
		touch($dir.'/.gitignore');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
			"/wp-content/themes/my-theme\n",
		], [
			"/wp-content/languages\n",
			"/node_modules\n",
			"/vendor\n",
			"/wp-content/themes/my-theme\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleFailedGitCommand()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'twitget',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
		], false, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([], sha1('foobar'), null, []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::err('oh no'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertTrue($result->isErr());
		$this->assertEquals('git command failed: oh no', $result->getErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleWithExplicitSrc()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
					'src' => 'foobar',
				],
			],
			'plugins' => [
				[
					'name' => 'twitget',
					'ref' => 'v1.4',
					'src' => 'foobar',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/twitget\n"
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'foobar', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'foobar', 'v1.4', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleWithoutRef()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'main', \Result\Result::ok('27ba906'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}

	public function testUpdateSingleNoGitignore()
	{
		$dir = $this->getDirWithDefaults();

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'themes' => 'git@git.govpress.com:wordpress-themes/',
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		file_put_contents($dir.'/whippet.json', 'foobar');

		$gitignore = $this->getGitignore([], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], true, true);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$whippetLock = $this->getWhippetLockWritable([
			['themes', 'my-theme', 'git@git.govpress.com:wordpress-themes/my-theme', '27ba906'],
		], sha1('foobar'), $dir.'/whippet.lock', []);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('27ba906'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('themes/my-theme');
		$output = ob_get_clean();

		$this->assertFalse($result->isErr());
		$this->assertEquals("[Updating themes/my-theme]\n", $output);
	}


	public function testUpdateSingleWithoutTranslations()
	{
		$dir = $this->getDirWithDefaults();
		file_put_contents($dir.'/whippet.json', 'foobar');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		$whippetLock = $this->getWhippetLockWritable([
			['plugins', 'my-plugin', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'd961c3d'],
		], sha1('foobar'), $dir.'/whippet.lock', []);

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));

		$gitignore = $this->getGitignore(["/wp-content/themes/my-theme\n",
		"/wp-content/plugins/my-plugin\n", ], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$dependencies = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir),
		);

		ob_start();
		$result = $dependencies->updateSingle('plugins/my-plugin');
		$output = ob_get_clean();

		$this->assertEquals("[Updating plugins/my-plugin]\n", $output);
		$this->assertFalse($result->isErr());
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testUpdateSingleWithTranslations()
	{
		$dir = $this->getDirWithDefaults();
		file_put_contents($dir.'/whippet.json', 'foobar');

		$whippetJson = $this->getWhippetJson([
			'src' => [
				'plugins' => 'git@git.govpress.com:wordpress-plugins/',
			],
			'themes' => [
				[
					'name' => 'my-theme',
					'src' => 'git@git.govpress.com:wordpress-themes/',
					'ref' => 'v1.4',
				],
			],
			'plugins' => [
				[
					'name' => 'my-plugin',
					'ref' => 'v1.6',
				],
			],
			'languages' => [
				[
					'name' => 'en_GB'
				]
			],
		]);
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetJson', 'fromFile', $dir.'/whippet.json', \Result\Result::ok($whippetJson));

		$whippetLock = $this->getWhippetLockWritable([
			['languages', 'en_GB', 'https://example.com/translation/core/6.3.1/en_GB.zip', '6.3.1'],
			['languages', 'en_GB/plugins/my-plugin', 'https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip', '1.6'],
		], sha1('foobar'), $dir.'/whippet.lock', [
			[
				'themes', [
					[
						'name' => 'my-theme',
						'src' => 'git@git.govpress.com:wordpress-themes/my-theme',
						'revision' => 'c235f9b',
					],
				],
			],
			[
				'plugins', [
					[
						'name' => 'my-plugin',
						'src' => 'git@git.govpress.com:wordpress-plugins/my-plugin',
						'revision' => 'd961c3d',
					],
				],
			],
			[
				'languages', [
					[
						'name' => 'en_GB',
						'src' => 'https://example.com/translation/core/6.3.1/en_GB.zip',
						'revision' => '6.3.1'
					],
					[
						'name' => 'en_GB/plugins/my-plugin',
						'src' => 'https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip',
						'revision' => '1.6'
					],
				],
			],
	]);

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'v1.6', \Result\Result::ok('d961c3d'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'ls_remote', 'git@git.govpress.com:wordpress-themes/my-theme', 'v1.4', \Result\Result::ok('c235f9b'));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'tag_for_commit', 'git@git.govpress.com:wordpress-plugins/my-plugin', 'd961c3d', \Result\Result::ok('v1.6'));
		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Git\\Git', 'tag_for_commit', 'git@git.govpress.com:wordpress-themes/my-theme', 'c235f9b', \Result\Result::ok('v1.4'));

		$this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

		$gitignore = $this->getGitignore(["/wp-content/themes/my-theme\n",
		"/wp-content/plugins/my-plugin\n", ], [
			"/wp-content/themes/my-theme\n",
			"/wp-content/plugins/my-plugin\n",
			"/wp-content/languages\n",
		], true, false);
		$this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Gitignore', $dir, $gitignore);

		$fakeTranslationsApi = \Mockery::mock('alias:\\Dxw\\Whippet\\Models\\TranslationsApi');
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('languages', 'en_GB', '6.3.1', null)
		->andReturn(\Result\Result::ok(['https://example.com/translation/core/6.3.1/en_GB.zip', '6.3.1']));
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('plugins', 'en_GB', '1.6', 'my-plugin')
		->andReturn(\Result\Result::ok(['https://example.com/translation/plugin/my-plugin/1.6/en_GB.zip', '1.6']));
		$fakeTranslationsApi
		->shouldReceive('fetchLanguageSrcAndRevision')
		->with('themes', 'en_GB', '1.4', 'my-theme')
		->andReturn(\Result\Result::ok());

		$updater = new \Dxw\Whippet\Dependencies\Updater(
			$this->getFactory(),
			$this->getProjectDirectory($dir)
		);

		ob_start();
		$result = $updater->updateSingle('languages/en_GB');
		$output = ob_get_clean();

		$this->assertEquals("[Updating languages/en_GB]\n* No en_GB language pack available for my-theme 1.4.\n", $output);
		$this->assertFalse($result->isErr());
	}
}
