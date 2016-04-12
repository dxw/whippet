<?php

class Commands_Dependencies_Test extends PHPUnit_Framework_TestCase
{
    use \Helpers;

    private function getAllDeps()
    {
        $dir = $this->getDir();
        $installer = $this->getMockBuilder('\\Dxw\\Whippet\\Dependencies\\Installer')
        ->disableOriginalConstructor()
        ->setMethods([])
        ->getMock();
        $updater = $this->getMockBuilder('\\Dxw\\Whippet\\Dependencies\\Updater')
        ->disableOriginalConstructor()
        ->setMethods([])
        ->getMock();
        $migration = $this->getMockBuilder('\\Dxw\\Whippet\\Dependencies\\Migration')
        ->disableOriginalConstructor()
        ->setMethods([])
        ->getMock();

        return [$dir, $installer, $updater, $migration];
    }

    private function getCommandTester(
        \Dxw\Whippet\Factory $factory,
        /* string */ $cwd
    )
    {
        $app = new \Symfony\Component\Console\Application();
        $dependencies = new \Dxw\Whippet\Commands\Dependencies();

        $dependencies->inject(
            $factory,
            $cwd
        );

        $app->add($dependencies);

        $command = $app->find('dependencies');
        $commandTester = new \Symfony\Component\Console\Tester\CommandTester($command);

        return $commandTester;
    }

    public function testInstallSuccessful()
    {
        list($dir, $installer, $updater, $migration) = $this->getAllDeps();

        $projectDirectory = $this->getProjectDirectory($dir);

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\ProjectDirectory', 'find', $dir, \Result\Result::ok($projectDirectory));
        $factory = $this->getFactory();
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Dependencies\\Installer', $factory, $projectDirectory, $installer);

        $installer->expects($this->exactly(1))
        ->method('install')
        ->with()
        ->willReturn(\Result\Result::ok());

        $commandTester = $this->getCommandTester($this->getFactory(), $dir);
        $commandTester->execute([
            'command' => 'dependencies',
            'subcommand' => 'install',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertEquals('', $commandTester->getDisplay());
    }

    public function testInstallFailure()
    {
        list($dir, $installer, $updater, $migration) = $this->getAllDeps();

        $projectDirectory = $this->getProjectDirectory($dir);

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\ProjectDirectory', 'find', $dir, \Result\Result::ok($projectDirectory));
        $factory = $this->getFactory();
        $this->addFactoryNewInstance('\\Dxw\\Whippet\\Dependencies\\Installer', $factory, $projectDirectory, $installer);

        $installer->expects($this->exactly(1))
        ->method('install')
        ->with()
        ->willReturn(\Result\Result::err('oh noes!'));

        $commandTester = $this->getCommandTester($this->getFactory(), $dir);
        $commandTester->execute([
            'command' => 'dependencies',
            'subcommand' => 'install',
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertEquals("ERROR: oh noes!\n", $commandTester->getDisplay());
    }

    public function testInstallCannotFindProjectDir()
    {
        list($dir, $installer, $updater, $migration) = $this->getAllDeps();

        $this->addFactoryCallStatic('\\Dxw\\Whippet\\ProjectDirectory', 'find', $dir, \Result\Result::err('an error'));

        $commandTester = $this->getCommandTester($this->getFactory(), $dir);
        $commandTester->execute([
            'command' => 'dependencies',
            'subcommand' => 'install',
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertEquals("ERROR: an error\n", $commandTester->getDisplay());
    }
}
