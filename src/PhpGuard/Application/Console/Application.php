<?php

namespace PhpGuard\Application\Console;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpGuard\Application\Console\Command\StartCommand;
use PhpGuard\Application\Container;
use PhpGuard\Application\Guard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var ContainerInterface
     */
    private $container;

    private $initialized = false;

    public function __construct()
    {
        $this->initialize();
        parent::__construct('phpguard',Guard::VERSION);
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->loadCommands();
        return parent::run($input, $output);
    }

    private function initialize()
    {
        $container = new Container();
        $guard = new Guard();
        $guard->setupServices($container);
        $guard->loadConfiguration();

        $this->guard = $guard;
        $this->container = $container;
        $this->initialized = true;
    }

    private function loadCommands()
    {
        $this->add(new StartCommand());
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $container = $this->container;
        $this->setDispatcher($container->get('guard.dispatcher'));
        $container->set('guard.ui.input',$input);
        $container->set('guard.ui.output',$output);
        $container->set('guard.ui.application',$this);
        return parent::doRun($input, $output);
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}