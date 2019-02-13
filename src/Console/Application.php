<?php

namespace Thruster\Tool\ProjectGenerator\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Thruster\Tool\ProjectGenerator\Console\Command\CreateRepoCommand;
use Thruster\Tool\ProjectGenerator\Console\Command\EnableCICommand;
use Thruster\Tool\ProjectGenerator\Console\Command\GenerateCommand;

/**
 * Class Application
 *
 * @package Thruster\Tool\ProjectGenerator
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('Thruster Project Generator', '1.0');

        $this->add(new GenerateCommand());
        $this->add(new CreateRepoCommand());
        $this->add(new EnableCICommand());

        $this->setDefaultCommand('main');
    }

    /**
     * @inheritDoc
     */
    public function getLongVersion()
    {
        $version = parent::getLongVersion() .
            ' by <comment>Aurimas Niekis</comment>';

        return $version;
    }

}
