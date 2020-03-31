<?php

declare(strict_types=1);

namespace Thruster\Tool\ProjectGenerator\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class CreateRepoCommand.
 *
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class CreateRepoCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('create:repo')
            ->addOption(
                'organisation',
                'o',
                InputOption::VALUE_OPTIONAL,
                'GitHub Organisation Name',
                'ThrusterIO'
            )
            ->addOption(
                'private',
                'p',
                InputOption::VALUE_NONE,
                'Create a private repository',
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
        $organisation    = $input->getOption('organisation');

        $output->write('<info>Creating GitHub Repository: </info>');
        $output->writeln('<comment>' . $organisation . '/' . $name . '</comment>');

        $options = [
            'hub',
            'create',
        ];

        if ($input->getOption('private')) {
            $options[] = '-p';
        }

        $options[] = '-d ' . json_encode($composerJson['description']);
        $options[] = $organisation . '/' . $name;

        $process = new Process($options);
        $process->start();

        $process->wait(function ($type, $buffer): void {
            echo $buffer;
        });

        return $process->getExitCode();
    }
}
