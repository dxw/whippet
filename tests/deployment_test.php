<?php

class DeploymentTest extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    public function symlink()
    {
        if (!isset($this->symlinkCalls)) {
            $this->symlinkCalls = [];
        }

        $this->symlinkCalls[] = func_get_args();
    }

    private function getRelease(/* string */ $path, /* bool */ $createFiles)
    {
        mkdir($path);
        $releaseDir = $path.'/abc123';
        mkdir($releaseDir);

        $release = $this->getMockBuilder('\\Dxw\\Whippet\\Modules\\Release')
        ->disableOriginalConstructor()
        ->getMock();

        $createMethod = $release->expects($this->exactly(1))
        ->method('create')
        ->with(false);
        if ($createFiles) {
            $createMethod->will($this->returnCallback(function () use ($releaseDir) {
                touch($releaseDir.'/wp-login.php');
                mkdir($releaseDir.'/wp-includes');
                touch($releaseDir.'/wp-includes/wp-db.php');
                mkdir($releaseDir.'/wp-admin');
                touch($releaseDir.'/wp-admin/edit.php');
                mkdir($releaseDir.'/wp-content');
                mkdir($releaseDir.'/wp-content/themes');
                mkdir($releaseDir.'/wp-content/plugins');
                file_put_contents($releaseDir.'/wp-config.php', "<?php define('DB_NAME', 'foobar');\n");
                mkdir($releaseDir.'/wp-content/uploads');
            }));
        }

        $release->release_dir = $releaseDir;
        $release->deployed_commit = 'abc123';

        return $release;
    }

    private function getAllDirs()
    {
        $dir = $this->getDir();

        $projectDir = $dir.'/wp';
        $deployDir = $dir.'/deploy';
        $sharedDir = $deployDir.'/shared';

        // Create deploy directory files
        mkdir($deployDir);

        // Create shared dir files
        mkdir($sharedDir);
        file_put_contents($sharedDir.'/wp-config.php', "<?php define('DB_NAME', 'foobar');\n");
        mkdir($sharedDir.'/uploads');

        return [$projectDir, $deployDir, $sharedDir];
    }

    private function createProjectFiles(/* string */ $projectDir)
    {
        mkdir($projectDir);
        mkdir($projectDir.'/config');
        mkdir($projectDir.'/wp-content');
        mkdir($projectDir.'/wp-content/plugins');
        touch($projectDir.'/.gitignore');
    }

    public function testDeployReleaseDidNotValidate()
    {
        list($projectDir, $deployDir, $sharedDir) = $this->getAllDirs();
        $this->createProjectFiles($projectDir);

        touch($sharedDir.'/wp-config.php');

        $release = $this->getRelease($deployDir.'/releases', false);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Modules\\Release', $deployDir.'/releases', '', 0, $release);

        $deployment = new \Dxw\Whippet\Deployment(
            $this->getFactory(),
            $this->getProjectDirectory($projectDir),
            $deployDir
        );

        ob_start();
        $result = $deployment->deploy(false, 3);
        $output = ob_get_clean();

        $this->assertTrue($result->isErr());
        $this->assertEquals('release did not validate', $result->getErr());
        $this->assertEquals(implode("\n", [
            'Problems:',
            "\twp-login.php is missing; is WordPress properly deployed?",
            "\twp-includes/wp-db.php is missing; is WordPress properly deployed?",
            "\twp-admin/edit.php is missing; is WordPress properly deployed?",
            "\twp-content/themes is missing; is the app properly deployed?",
            "\twp-content/plugins is missing; is the app properly deployed?",
            "\twp-config.php is missing; did the symlinking fail?",
            "\twp-content/uploads is missing; did the symlinking fail?",
            '',
            'Release did not validate; it has been moved to: vfs://root/deploy/releases/abc123.broken',
        ]), $output);
    }

    public function testDeploySuccess()
    {
        list($projectDir, $deployDir, $sharedDir) = $this->getAllDirs();
        $this->createProjectFiles($projectDir);

        touch($sharedDir.'/wp-config.php');

        $release = $this->getRelease($deployDir.'/releases', true);
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Modules\\Release', $deployDir.'/releases', '', 0, $release);

        $deployment = new \Dxw\Whippet\Deployment(
            $this->getFactory(),
            $this->getProjectDirectory($projectDir),
            $deployDir
        );
        $deployment->symlink = [$this, 'symlink'];
        $deployment->realpath = function ($a) { return $a; };
        mkdir($deployDir.'/releases/1');
        mkdir($deployDir.'/releases/2');
        mkdir($deployDir.'/releases/3');
        mkdir($deployDir.'/releases/4');
        $deployment->glob = function () use ($deployDir) { return [
            $deployDir.'/releases/1',
            $deployDir.'/releases/2',
            $deployDir.'/releases/3',
            $deployDir.'/releases/4',
        ]; };

        ob_start();
        $result = $deployment->deploy(false, 3);
        $output = ob_get_clean();

        $this->assertEquals([
            [$deployDir.'/releases/abc123', $deployDir.'/releases/abc123/../../current'],
        ], $this->symlinkCalls);

        $this->assertFalse($result->isErr());
        $this->assertEquals('', $output);
        $this->assertTrue(file_exists($deployDir.'/releases/1'));
        $this->assertTrue(file_exists($deployDir.'/releases/2'));
        $this->assertTrue(file_exists($deployDir.'/releases/3'));
        $this->assertFalse(file_exists($deployDir.'/releases/4'));
    }
}
