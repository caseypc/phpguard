<?php

namespace PhpGuard\Application\Listener;

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpGuard\Application\Container\ContainerAware;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\ApplicationEvents;
use PhpGuard\Application\Event\GenericEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigurationListener
 *
 */
class ConfigurationListener extends ContainerAware implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            ApplicationEvents::preLoadConfig => 'preLoad',
            ApplicationEvents::postLoadConfig => 'postLoad',
        );
    }

    public function preLoad(GenericEvent $event)
    {
        /* @var PhpGuard $guard */
        $guard = $event->getContainer()->get('phpguard');
        $guard->setOptions(array());

        $configFile = null;
        if(is_file($file=getcwd().'/phpguard.yml')){
            $configFile = $file;
        }elseif(is_file($file = getcwd().'/phpguard.yml.dist')){
            $configFile = $file;
        }
        if(is_null($configFile)){
            throw new ConfigurationException('Can not find configuration file "phpguard.yml" or "phpguard.yml.dist" in the current directory');
        }
        $event->getContainer()->setParameter('config.file',$configFile);
    }

    public function postLoad()
    {
        $this->setupParameters();
        /* @var \PhpGuard\Application\Plugin\PluginInterface $plugin */
        /* @var \PhpGuard\Application\Log\Logger $logger */
        $container = $this->container;
        $plugins = $container->getByPrefix('plugins');
        $logger = $container->get('logger');
        foreach($plugins as $plugin){
            if(!$plugin->isActive()){
                continue;
            }
            $plogger = new Logger($plugin->getTitle());
            $plogger->pushHandler($container->get('logger.handler'));
            $plugin->setLogger($plogger);
            $plugin->configure();
            $plogger->addCommon('Plugin <comment>'.$plugin->getTitle().'</comment> activated');
        }
    }

    private function setupParameters()
    {
        $container = $this->container;

        if(is_null($container->getParameter('phpguard.use_tty',null))){
            $container->setParameter('phpguard.use_tty',true);
        }
    }
}