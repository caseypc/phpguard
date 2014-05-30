<?php

namespace PhpGuard\Application\Plugin;

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
use PhpGuard\Application\Runner;
use PhpGuard\Application\Watcher;
use PhpGuard\Application\Event\EvaluateEvent;
use PhpGuard\Listen\Util\PathUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Plugin
 *
 */
abstract class Plugin extends ContainerAware implements PluginInterface
{
    /**
     * @var array
     */
    protected $watchers = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var boolean
     */
    protected $active = false;

    /**
     * @return void
     */
    public function configure(){}

    /**
     * @param Watcher $watcher
     */
    public function addWatcher(Watcher $watcher)
    {
        $this->watchers[] = $watcher;
    }

    /**
     * @return array
     * @author Anthonius Munthi <me@itstoni.com>
     */
    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * Reset watchers into an empty array
     * return void
     */
    public function reload()
    {
        $this->watchers = array();
        $this->active = false;
    }

    /**
     * @param   EvaluateEvent $event
     * @return  array
     */
    public function getMatchedFiles(EvaluateEvent $event)
    {
        $container = $this->container;

        $filtered = array();
        $files = $event->getFiles();
        foreach($files as $file){
            if($file==$container->getParameter('config.file')){
                continue;
            }
            if($matched=$this->matchFile($file)){
                if(!$matched instanceof SplFileInfo){
                    $matched = PathUtil::createSplFileInfo(getcwd(),$matched);
                }
                $filtered[] = $matched;
            }
        }
        return $filtered;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param boolean $active
     *
     * @return Plugin
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param   mixed $tag
     *
     * @return  void
     */
    public function addTag($tag)
    {
        $this->options['tag'][] = $tag;
    }

    /**
     * @param $file
     * @return string
     * @author Anthonius Munthi <me@itstoni.com>
     */
    private function matchFile($file)
    {
        $tags = $this->container->getParameter('filter.tags',array());
        /* @var Watcher $watcher */
        foreach($this->watchers as $watcher){
            if(false===$watcher->hasTags($tags)){
                $options = $watcher->getOptions();
                $this->logger->debug('Unmatched tags',array('watcher.tags'=>$options['tags'],'app.tags'=>$tags));
                continue;
            }
            if($matched = $watcher->matchFile($file)){
                if($watcher->lint($file)){
                    return $matched;
                }
            }
        }
        return false;
    }
}