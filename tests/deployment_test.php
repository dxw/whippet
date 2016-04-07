<?php

class DeploymentTest extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    private function getRelease(/* string */ $path)
    {
        $release = $this->getMockBuilder('\\Dxw\\Whippet\\Modules\\Release')
        ->disableOriginalConstructor()
        ->getMock();

        $release->expects($this->exactly(1))
        ->method('create')
        ->with(false);

        $release->release_dir = $path;

        return $release;
    }

    private function getAllDirs()
    {
        $dir = $this->getDir();

        $projectDir = $dir.'/wp';
        $deployDir = $dir.'/deploy';
        $sharedDir = $dir.'/shared';

        // Create deploy directory files
        mkdir($deployDir);

        // Create shared dir files
        mkdir($sharedDir);

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

        $release = $this->getRelease($deployDir.'/releases');
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
            "\tuploads directory is not in the shared directory.",
            "\twp-config.php doesn't contain DB_NAME; is it valid?",
            "\twp-config.php is missing; did the symlinking fail?",
            "\twp-content/uploads is missing; did the symlinking fail?",
            '',
            'Release did not validate; it has been moved to: vfs://root/deploy/releases.broken',
        ]), $output);
    }
}
