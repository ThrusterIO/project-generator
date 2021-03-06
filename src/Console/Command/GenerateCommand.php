<?php

declare(strict_types=1);

namespace Thruster\Tool\ProjectGenerator\Console\Command;

use GitWrapper\GitWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class GenerateCommand.
 *
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class GenerateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('main');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $output->writeln(
            [
                $this->getApplication()->getLongVersion(),
                '',
            ]
        );

        $question = new Question('<question>Please enter project name in title case:</question> ');

        $name = $helper->ask($input, $output, $question);

        if (empty($name)) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Project name cannot be empty</error>',
                ]
            );

            return 1;
        }

        if (false === ctype_upper($name[0])) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Project name must be title case</error>',
                ]
            );

            return 1;
        }

        $nameCan     = strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', str_replace('_', '-', $name)));
        $projectPath = getcwd() . '/' . $nameCan;

        if (file_exists($projectPath) || is_dir($projectPath)) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Project with name "' . $nameCan . '" already exists</error>',
                ]
            );

            return 1;
        }

        $projectTypes = ['Component', 'Tool', 'Bundle', 'Action'];
        $question     = new Question('<question>Please enter project type (Component):</question> ', 'Component');
        $question->setAutocompleterValues($projectTypes);

        $projectType = $helper->ask($input, $output, $question);

        if (empty($projectType)) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Project type cannot be empty</error>',
                ]
            );

            return 1;
        }

        if (false === ctype_upper($projectType[0])) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Project type must be title case</error>',
                ]
            );

            return 1;
        }

        $author    = rtrim(shell_exec('git config --get user.name'));
        $authorSug = '';
        if (null !== $author) {
            $authorSug = '(' . $author . ')';
        }
        $question = new Question('<question>Please enter author ' . $authorSug . ':</question> ', $author);

        $author = $helper->ask($input, $output, $question);

        if (empty($author)) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Author cannot be empty</error>',
                ]
            );

            return 1;
        }

        $email    = rtrim(shell_exec('git config --get user.email'));
        $emailSug = '';
        if (null !== $email) {
            $emailSug = '(' . $email . ')';
        }
        $question = new Question('<question>Please enter email ' . $emailSug . ':</question> ', $email);

        $email = $helper->ask($input, $output, $question);

        if (empty($email)) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Email cannot be empty</error>',
                ]
            );

            return 1;
        }

        $repo     = 'git@github.com:ThrusterIO/' . $nameCan . '.git';
        $question = new Question('<question>Please enter git repository (' . $repo . '):</question> ', $repo);

        $repo = $helper->ask($input, $output, $question);

        if (empty($email)) {
            $output->writeln(
                [
                    '',
                    '<error>Error: Git repository cannot be empty</error>',
                ]
            );

            return 1;
        }

        $output->writeln(['']);

        $year = date('Y');

        $output->write('<info>Cloning project template: </info>');
        $wrapper = new GitWrapper();
        $wrapper->cloneRepository('git@github.com:ThrusterIO/project-template.git', $projectPath);
        $output->writeln('<comment>Done</comment>');

        $fs = new Filesystem();
        $output->write('<info>Removing .git folder: </info>');
        $fs->remove($projectPath . '/.git');
        $output->writeln('<comment>Done</comment>');

        $output->write('<info>Removing src/.gitkeep file: </info>');
        $fs->remove($projectPath . '/src/.gitkeep');
        $output->writeln('<comment>Done</comment>');

        $output->write('<info>Removing tests/.gitkeep file: </info>');
        $fs->remove($projectPath . '/tests/.gitkeep');
        $output->writeln('<comment>Done</comment>');

        $output->write('<info>Initialized empty Git repository: </info>');
        $git = $wrapper->init($projectPath);
        $output->writeln('<comment>Done</comment>');

        $finder = new Finder();
        $finder->in($projectPath)->ignoreDotFiles(false)->files();

        $output->writeln(['', '<info>Applying variables to files: </info>']);
        $progress = new ProgressBar($output, count($finder));
        $progress->setFormat('debug');
        $progress->start();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $searchFor = [
                '${NAME}$',
                '${NAME_CAN}$',
                '${PROJECT_TYPE}$',
                '${YEAR}$',
                '${AUTHOR}$',
                '${EMAIL}$',
            ];

            $replaceWith = [
                $name,
                $nameCan,
                $projectType,
                $year,
                $author,
                $email,
            ];

            $progress->setMessage($file->getFilename());

            file_put_contents(
                $file->getRealPath(),
                str_replace($searchFor, $replaceWith, $file->getContents())
            );

            $progress->advance();
        }

        $progress->finish();

        $git->remote('add', 'origin', $repo);
        $git->add('./');

        $output->writeln(['', '', '<comment>Finished.</comment>']);

        return 0;
    }
}
