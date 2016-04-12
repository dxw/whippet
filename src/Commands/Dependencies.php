<?php

namespace Dxw\Whippet\Commands;

class Dependencies extends \Symfony\Component\Console\Command\Command
{
    public function inject(
        \Dxw\Whippet\Factory $factory,
        /* string */ $cwd
    )
    {
        $this->factory = $factory;
        $this->cwd = $cwd;
    }

    protected function configure()
    {
        $this->setName('dependencies');
        $this->addArgument(
            'subcommand',
            \Symfony\Component\Console\Input\InputArgument::REQUIRED,
            'The sub-command'
        );
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    )
    {
        $result = $this->factory->callStatic('\\Dxw\\Whippet\\ProjectDirectory', 'find', $this->cwd);
        if ($result->isErr()) {
            $output->writeln(sprintf("ERROR: %s", $result->getErr()));

            return 1;
        }
        $projectDirectory = $result->unwrap();

        $installer = $this->factory->newInstance('\\Dxw\\Whippet\\Dependencies\\Installer', $this->factory, $projectDirectory);

        $result = $installer->install();
        if ($result->isErr()) {
            $output->writeln(sprintf("ERROR: %s", $result->getErr()));

            return 1;
        }
    }
}
