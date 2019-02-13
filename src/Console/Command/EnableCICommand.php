<?php

namespace Thruster\Tool\ProjectGenerator\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Thruster\Tool\ProjectGenerator\Console\PackagistAdder;

/**
 * Class EnableCICommand
 *
 * @package Thruster\Tool\ProjectGenerator\Console\Command
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class EnableCICommand extends Command
{
    protected function configure()
    {
        $this->setName('enable:ci')
            ->addOption(
                'organisation',
                'o',
                InputOption::VALUE_OPTIONAL,
                'GitHub Organisation Name',
                'ThrusterIO'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workDir = getcwd();

        if (false === file_exists($workDir . '/composer.json')) {
            $output->writeln('<error>Not a project directory (composer.json is missing)</error>');

            return 1;
        }

        $composerJson = json_decode(file_get_contents($workDir . '/composer.json'), true);

        [$vendor, $name] = explode('/', $composerJson['name']);
        $organisation = $input->getOption('organisation');

        $output->write('<info>Enabling Travis on </info>');
        $output->writeln('<comment>' . $organisation . '/' . $name . '</comment>');

        $this->exec(['travis', 'enable']);

        $packagistAdder = new PackagistAdder();

        return $packagistAdder->execute($output);
    }

    private function exec(array $args)
    {
        $process = new Process($args);
        $process->start();

        $process->wait(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
