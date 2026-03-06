<?php

use Kahlan\Plugin\Double;
use Kahlan\Arg;
use org\bovigo\vfs\vfsStream;

describe(\Dxw\Whippet\Dependencies\Installer::class, function () {
	beforeEach(function () {
		$this->root = vfsStream::setup();
		$this->dir = $this->root->url();
		$this->factory = Double::instance(['extends' => \Dxw\Whippet\Factory::class]);
		$this->projectDirectory = Double::instance([
			'extends' => \Dxw\Whippet\ProjectDirectory::class,
			'args' => [$this->dir]
		]);
		$this->inspectionChecker = Double::instance();
		allow($this->inspectionChecker)->toReceive('check')->andReturn(\Result\Result::ok(''));
		$this->installer = new \Dxw\Whippet\Dependencies\Installer(
			$this->factory,
			$this->projectDirectory,
			$this->inspectionChecker
		);
	});

	$this->getArchivedWarning = function () {
		return <<<'EOT'
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!! WARNING: GitHub repo is archived. This dependency !!
!! should be replaced before the repo is removed.    !!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

EOT;
	};

	describe('->installAll()', function () {
		context('when whippet.json is missing', function () {
			it('returns an error', function () {
				$result = $this->installer->installAll();

				expect($result->isErr())->toBe(true);
				expect($result->getErr())->toEqual('whippet.json not found');
			});
		});

		context('when whippet.lock is missing', function () {
			it('returns an error', function () {
				touch($this->dir.'/whippet.json');
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::err('file not found'));

				$result = $this->installer->installAll();

				expect($result->isErr())->toBe(true);
				expect($result->getErr())->toEqual('whippet.lock: file not found');
			});
		});

		context('when hashes mismatch', function () {
			it('returns an error', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn('123123');
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$result = $this->installer->installAll();

				expect($result->isErr())->toBe(true);
				expect($result->getErr())->toEqual('mismatched hash - run `whippet dependencies update` first');
			});
		});

		context('when whippet.lock is empty', function () {
			it('outputs a message and returns ok', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->andReturn([]);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(false);
				})->toEcho("whippet.lock contains nothing to install\n");
			});
		});

		context('when everything is correct', function () {
			it('installs all dependencies', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->with('themes')->andReturn([
					[
						'name' => 'my-theme',
						'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
						'revision' => '27ba906',
					]
				]);
				allow($whippetLock)->toReceive('getDependencies')->with('plugins')->andReturn([
					[
						'name' => 'my-plugin',
						'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
						'revision' => '123456',
					],
					[
						'name' => 'another-plugin',
						'src' => 'git@github.com:dxw-wordpress-plugins/another-plugin',
						'revision' => '789abc',
					]
				]);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$git = Double::instance();
				allow($this->factory)->toReceive('newInstance')->andReturn($git);
				allow($git)->toReceive('is_repo')->andReturn(false);
				allow($git)->toReceive('clone_repo')->andReturn(true, true, true);
				allow($git)->toReceive('checkout')->andReturn(true, true, true);

				$warning_msg = "#############################################\n#                                           #\n#  WARNING: No inspections for this plugin  #\n#                                           #\n#############################################";
				allow($this->inspectionChecker)->toReceive('check')->andReturn(\Result\Result::ok(''), \Result\Result::ok($warning_msg), \Result\Result::ok("Inspections for this plugin:\n* 01/05/2015 - 0.1.3 - No issues found - https://advisories.dxw.com/plugins/another_plugin/"));

				$expectedOutput = <<<'EOT'
[Adding themes/my-theme]
git clone output
git checkout output

[Adding plugins/my-plugin]
git clone output
git checkout output
#############################################
#                                           #
#  WARNING: No inspections for this plugin  #
#                                           #
#############################################

[Adding plugins/another-plugin]
git clone output
git checkout output
Inspections for this plugin:
* 01/05/2015 - 0.1.3 - No issues found - https://advisories.dxw.com/plugins/another_plugin/


EOT;
				// Kahlan's toEcho doesn't handle multiple echos in a row as easily for large strings if there are gaps,
				// but let's see. The installer echoes "git clone output\n" etc.
				// Wait, the Git mock in original test was echoing. I need to do the same if I want to match output exactly.
				allow($git)->toReceive('clone_repo')->andRun(function () {
					echo "git clone output\n";
					return true;
				});
				allow($git)->toReceive('checkout')->andRun(function () {
					echo "git checkout output\n";
					return true;
				});

				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(false);
				})->toEcho($expectedOutput);
			});
		});

		context('when inspections API is unavailable', function () {
			it('outputs the error and continues', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->andReturn(
					[],
					[
						[
							'name' => 'my-plugin',
							'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
							'revision' => '123456',
						]
					]
				);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$git = Double::instance();
				allow($this->factory)->toReceive('newInstance')->andReturn($git);
				allow($git)->toReceive('is_repo')->andReturn(false);
				allow($git)->toReceive('clone_repo')->andRun(function () {
					echo "git clone output\n";
					return true;
				});
				allow($git)->toReceive('checkout')->andRun(function () {
					echo "git checkout output\n";
					return true;
				});

				allow($this->inspectionChecker)->toReceive('check')->andReturn(\Result\Result::err('foooooo'));

				$expectedOutput = <<<'EOT'
[Adding plugins/my-plugin]
git clone output
git checkout output
[ERROR] foooooo


EOT;
				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(false);
				})->toEcho($expectedOutput);
			});
		});

		context('when installing an archived repo', function () {
			it('outputs archived warning', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->andReturn(
					[
						[
							'name' => 'my-theme',
							'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
							'revision' => '27ba906',
						]
					],
					[]
				);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$git = Double::instance();
				allow($this->factory)->toReceive('newInstance')->andReturn($git);
				allow($git)->toReceive('is_repo')->andReturn(false);

				$archived_warning = $this->getArchivedWarning();

				allow($git)->toReceive('clone_repo')->andRun(function () use ($archived_warning) {
					echo $archived_warning;
					echo "git clone output\n";
					return true;
				});
				allow($git)->toReceive('checkout')->andRun(function () use ($archived_warning) {
					echo $archived_warning;
					echo "git checkout output\n";
					return true;
				});

				allow($this->inspectionChecker)->toReceive('check')->andReturn(\Result\Result::ok(''));

				$expectedOutput = "[Adding themes/my-theme]\n" . $archived_warning . "git clone output\n" . $archived_warning . "git checkout output\n\n";

				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(false);
				})->toEcho($expectedOutput);
			});
		});

		context('when theme is already cloned', function () {
			it('just checks out the revision', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');
				mkdir($this->dir.'/wp-content/themes/my-theme', 0777, true);

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->andReturn(
					[
						[
							'name' => 'my-theme',
							'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
							'revision' => '27ba906',
						]
					],
					[]
				);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$git = Double::instance();
				allow($this->factory)->toReceive('newInstance')->andReturn($git);
				allow($git)->toReceive('is_repo')->andReturn(true);
				allow($git)->toReceive('checkout')->andRun(function () {
					echo "git checkout output\n";
					return true;
				});

				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(false);
				})->toEcho("[Checking themes/my-theme]\ngit checkout output\n\n");
			});
		});

		context('when clone fails', function () {
			it('returns an error', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->andReturn(
					[
						[
							'name' => 'my-theme',
							'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
							'revision' => '27ba906',
						]
					],
					[]
				);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$git = Double::instance();
				allow($this->factory)->toReceive('newInstance')->andReturn($git);
				allow($git)->toReceive('is_repo')->andReturn(false);
				allow($git)->toReceive('clone_repo')->andRun(function () {
					echo "git clone output\n";
					return false;
				});

				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(true);
					expect($result->getErr())->toEqual('could not clone repository');
				})->toEcho("[Adding themes/my-theme]\ngit clone output\n");
			});
		});

		context('when checkout fails', function () {
			it('returns an error', function () {
				file_put_contents($this->dir.'/whippet.json', 'foobar');
				touch($this->dir.'/whippet.lock');

				$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
				allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
				allow($whippetLock)->toReceive('getDependencies')->andReturn(
					[
						[
							'name' => 'my-theme',
							'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
							'revision' => '27ba906',
						]
					],
					[]
				);
				allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

				$git = Double::instance();
				allow($this->factory)->toReceive('newInstance')->andReturn($git);
				allow($git)->toReceive('is_repo')->andReturn(false);
				allow($git)->toReceive('clone_repo')->andRun(function () {
					echo "git clone output\n";
					return true;
				});
				allow($git)->toReceive('checkout')->andRun(function () {
					echo "git checkout output\n";
					return false;
				});

				expect(function () {
					$result = $this->installer->installAll();
					expect($result->isErr())->toBe(true);
					expect($result->getErr())->toEqual('could not checkout revision');
				})->toEcho("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n");
			});
		});
	});

	describe('->installSingle()', function () {
		it('installs a single dependency', function () {
			file_put_contents($this->dir.'/whippet.json', 'foobar');
			touch($this->dir.'/whippet.lock');

			$whippetLock = Double::instance(['extends' => \Dxw\Whippet\Files\WhippetLock::class, 'args' => [[]]]);
			allow($whippetLock)->toReceive('getHash')->andReturn(sha1('foobar'));
			allow($whippetLock)->toReceive('getDependencies')->with('themes')->andReturn([
				[
					'name' => 'my-theme',
					'src' => 'git@github.com:dxw-wordpress-themes/my-theme',
					'revision' => '27ba906',
				]
			]);
			allow($whippetLock)->toReceive('getDependencies')->with('plugins')->andReturn([
				[
					'name' => 'my-plugin',
					'src' => 'git@github.com:dxw-wordpress-plugins/my-plugin',
					'revision' => '123456',
				],
				[
					'name' => 'another-plugin',
					'src' => 'git@github.com:dxw-wordpress-plugins/another-plugin',
					'revision' => '789abc',
				],
			]);

			allow(\Dxw\Whippet\Files\WhippetLock::class)->toReceive('::fromFile')->andReturn(\Result\Result::ok($whippetLock));

			$git = Double::instance();
			allow($this->factory)->toReceive('newInstance')->andReturn($git);
			allow($git)->toReceive('is_repo')->andReturn(true);
			allow($git)->toReceive('checkout')->andRun(function () {
				echo "git checkout output\n";
				return true;
			});

			allow($this->inspectionChecker)->toReceive('check')->andReturn(\Result\Result::ok("Inspections for this plugin:\n* 01/05/2015 - No issues found - https://advisories.dxw.com/plugins/my-plugin/"));

			$expectedOutput = <<<'EOT'
[Checking plugins/my-plugin]
git checkout output
Inspections for this plugin:
* 01/05/2015 - No issues found - https://advisories.dxw.com/plugins/my-plugin/


EOT;
			expect(function () {
				$result = $this->installer->installSingle('plugins/my-plugin');
				expect($result->isErr())->toBe(false);
			})->toEcho($expectedOutput);
		});
	});
});
