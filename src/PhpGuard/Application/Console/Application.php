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

use PhpGuard\Application\Container;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Interfaces\ContainerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application
 *
 */
class Application extends BaseApplication
{
    /**
     * @var PhpGuard
     */
    private $guard;

    /**
     * @var ContainerInterface
     */
    private $container;

    private $initialized = false;

    public function __construct()
    {
        parent::__construct('phpguard',PhpGuard::VERSION);
        $this->initialize();
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->initialize();

        $container = $this->container;
        $this->setDispatcher($container->get('dispatcher'));
        $container->set('ui.input',$input);
        $container->set('ui.output',$output);

        if($input->hasParameterOption(array('--tags','-t'))){
            $tags = $input->getParameterOption(array('--tags','-t'));
            $tags = explode(',',$tags);
            $container->setParameter('filter.tags',$tags);
        }


        $command = $this->getCommandName($input);
        if($command==''){
            /* @var Shell $shell */
            $shell = $container->get('ui.shell');
            if(!$shell->isRunning()){
                $shell->start();
            }
            return 0;
        }
        return parent::doRun($input, $output);
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);

        $formatter = $output->getFormatter();
        $formatter->setStyle('log-error',new OutputFormatterStyle('red'));
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $options = $definition->getOptions();

        $options['tags'] = new InputOption(
            'tags',
            't',
            InputOption::VALUE_OPTIONAL,
            'Run only for this tags'
        );

        $definition->setOptions($options);

        return $definition;
    }

    private function initialize()
    {
        if($this->initialized){
            return;
        }
        $container = new Container();
        $container->set('ui.application',$this);

        try{
            $guard = new PhpGuard();
            $guard->setContainer($container);
            $guard->setupServices();
            $guard->loadConfiguration();
        }catch(\Exception $e){
            $this->renderException($e, new ConsoleOutput());
            exit(1);
        }
        $this->guard = $guard;
        $this->container = $container;
        $this->initialized = true;
    }

}