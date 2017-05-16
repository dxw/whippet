<?php

class DependenciesInstallerTest extends \PHPUnit_Framework_TestCase
{
    use \Helpers;

    public function tearDown()
    {
        \Mockery::close();
    }

    public function testInstallAll()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $my_theme = [
            'name' => 'my-theme',
            'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
            'revision' => '27ba906',
        ];

        $my_plugin = [
            'name' => 'my-plugin',
            'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
            'revision' => '123456',
        ];

        $another_plugin = [
            'name' => 'another-plugin',
            'src' => 'git@git.dxw.net:wordpress-plugins/another-plugin',
            'revision' => '789abc',
        ];

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                $my_theme,
            ],
            'plugins' => [
                $my_plugin,
                $another_plugin,
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', '27ba906');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);
        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);
        $gitAnotherPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/another-plugin', '789abc');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/another-plugin', $gitAnotherPlugin);

        $inspection_check_results = function ($type, $dep) {
            $warning_msg = <<<'EOT'
#############################################
#                                           #
#  WARNING: No inspections for this plugin  #
#                                           #
#############################################
EOT;

            return [
                'themes' =>  [
                    'my-theme' => \Result\Result::ok('')
                ],
                'plugins' => [
                    'my-plugin' =>  \Result\Result::ok($warning_msg),
                    'another-plugin' => \Result\Result::ok("Inspections for this plugin:\n* 01/05/2015 - 0.1.3 - No issues found - https://security.dxw.com/plugins/another_plugin/")
                ]
            ][$type][$dep['name']];
        };

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionCheckerWithResults($inspection_check_results)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
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
* 01/05/2015 - 0.1.3 - No issues found - https://security.dxw.com/plugins/another_plugin/


EOT;
        $this->assertEquals($expectedOutput, $output);
    }

    public function testInspectionsApiUnavailable()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $my_plugin = [
            'name' => 'my-plugin',
            'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
            'revision' => '123456',
        ];

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [],
            'plugins' => [
                $my_plugin,
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);

        $inspection_check_results = function ($type, $dep) {
            return [
                'plugins' => [
                    'my-plugin' =>  \Result\Result::err('foooooo'),
                ]
            ][$type][$dep['name']];
        };

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionCheckerWithResults($inspection_check_results)
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $expectedOutput = <<<'EOT'
[Adding plugins/my-plugin]
git clone output
git checkout output
[ERROR] foooooo


EOT;

        $this->assertEquals($expectedOutput, $output);
    }


    public function testInstallAllThemeAlreadyCloned()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        mkdir($dir.'/wp-content/themes/my-theme');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $git = $this->getGit(true, null, '27ba906');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $git);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("[Checking themes/my-theme]\ngit checkout output\n\n", $output);
    }

    public function testInstallAllMissingWhippetJson()
    {
        $dir = $this->getDir();

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.json not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallAllMissingWhippetLock()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::err('file not found'));

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('whippet.lock: file not found', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallAllWrongHash()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock('123123', []);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertEquals(true, $result->isErr());
        $this->assertEquals('mismatched hash - run `whippet dependencies update` first', $result->getErr());
        $this->assertEquals('', $output);
    }

    public function testInstallAllCloneFails()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, ['with' => 'git@git.dxw.net:wordpress-themes/my-theme', 'return' => false], null);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not clone repository', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\n", $output);
    }

    public function testInstallAllCheckoutFails()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', ['with' => '27ba906', 'return' => false]);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not checkout revision', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n", $output);
    }

    public function testInstallAllBlankLockfile()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installAll();
        $output = ob_get_clean();

        $this->assertFalse($result->isErr());
        $this->assertEquals("whippet.lock contains nothing to install\n", $output);
    }

    public function testInstallSingle()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
                    'revision' => '123456',
                ],
                [
                    'name' => 'another-plugin',
                    'src' => 'git@git.dxw.net:wordpress-plugins/another-plugin',
                    'revision' => '789abc',
                ],
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyPlugin = $this->getGit(true, null, '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);

        $inspection_check_results = function ($type, $dep) {
            return [
                'plugins' => [
                    'my-plugin' => \Result\Result::ok("Inspections for this plugin:\n* 01/05/2015 - No issues found - https://security.dxw.com/plugins/my-plugin/")
                ]
            ][$type][$dep['name']];
        };

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionCheckerWithResults($inspection_check_results)
        );
        ob_start();
        $result = $dependencies->installSingle('plugins/my-plugin');
        $output = ob_get_clean();

        $expectedOutput = <<<'EOT'
[Checking plugins/my-plugin]
git checkout output
Inspections for this plugin:
* 01/05/2015 - No issues found - https://security.dxw.com/plugins/my-plugin/


EOT;
        $this->assertEquals($expectedOutput, $output);
        $this->assertFalse($result->isErr());
    }

    public function testInstallSingleAlreadyCloned()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [
                [
                    'name' => 'my-plugin',
                    'src' => 'git@git.dxw.net:wordpress-plugins/my-plugin',
                    'revision' => '123456',
                ],
                [
                    'name' => 'another-plugin',
                    'src' => 'git@git.dxw.net:wordpress-plugins/another-plugin',
                    'revision' => '789abc',
                ],
            ],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyPlugin = $this->getGit(false, 'git@git.dxw.net:wordpress-plugins/my-plugin', '123456');
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/plugins/my-plugin', $gitMyPlugin);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );
        ob_start();
        $result = $dependencies->installSingle('plugins/my-plugin');
        $output = ob_get_clean();

        $this->assertEquals("[Adding plugins/my-plugin]\ngit clone output\ngit checkout output\n\n", $output);
        $this->assertFalse($result->isErr());
    }

    public function testInstallSingleCloneFails()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, ['with' => 'git@git.dxw.net:wordpress-themes/my-theme', 'return' => false], null);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not clone repository', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\n", $output);
    }

    public function testInstallSingleCheckoutFails()
    {
        $dir = $this->getDir();
        file_put_contents($dir.'/whippet.json', 'foobar');
        file_put_contents($dir.'/whippet.lock', 'foobar');

        $whippetLock = $this->getWhippetLock(sha1('foobar'), [
            'themes' => [
                [
                    'name' => 'my-theme',
                    'src' => 'git@git.dxw.net:wordpress-themes/my-theme',
                    'revision' => '27ba906',
                ],
            ],
            'plugins' => [],
        ]);
        $this->addFactoryCallStatic('\\Dxw\\Whippet\\Files\\WhippetLock', 'fromFile', $dir.'/whippet.lock', \Result\Result::ok($whippetLock));

        $gitMyTheme = $this->getGit(false, 'git@git.dxw.net:wordpress-themes/my-theme', ['with' => '27ba906', 'return' => false]);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Git\\Git', $dir.'/wp-content/themes/my-theme', $gitMyTheme);

        $dependencies = new \Dxw\Whippet\Dependencies\Installer(
            $this->getFactory(),
            $this->getProjectDirectory($dir),
            $this->fakeInspectionChecker()
        );

        ob_start();
        $result = $dependencies->installSingle('themes/my-theme');
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('could not checkout revision', $result->getErr());
        $this->assertEquals("[Adding themes/my-theme]\ngit clone output\ngit checkout output\n", $output);
    }

    private function fakeInspectionChecker()
    {
        return \Mockery::mock('\\Dxw\\Whippet\\Services\\InspectionChecker')
            ->shouldReceive('check')
            ->andReturn(\Result\Result::ok(''))
            ->getMock();
    }

    private function fakeInspectionCheckerWithResults($result_function)
    {
        return \Mockery::mock('\\Dxw\\Whippet\\Services\\InspectionChecker')
            ->shouldReceive('check')
            ->andReturnUsing($result_function)
            ->getMock();
    }
}
