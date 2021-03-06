<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests;

use PhpGuard\Application\Functional\TestPlugin;
use PhpGuard\Application\PhpGuard;
use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Util\Filesystem;

class PhpGuardTest extends TestCase
{
    public function testShouldConfigureOptions()
    {
        $phpGuard = new PhpGuard();

        $phpGuard->setOptions(array(
            'ignores' => 'test'
        ));
        $options = $phpGuard->getOptions();
        $this->assertSame(array('test'),$options['ignores']);

        $ignores = array('foo','bar');
        $phpGuard->setOptions(array(
            'ignores' => $ignores
        ));
        $options = $phpGuard->getOptions();

        $this->assertSame($ignores,$options['ignores']);
    }

    public function testShouldSetupListenProperly()
    {
        $this->getTester()->run('-vvv');
        $listener = static::$container->get('listen.listener');
        $this->assertInstanceOf('PhpGuard\\Listen\\Listener',$listener);
        $this->assertEquals(doubleval(0.01*1000000),$listener->getLatency());
        $this->assertContains('foo',$listener->getIgnores());
    }

    public function testShouldLoadPlugins()
    {
        $container = static::$container;
        $this->assertTrue($container->get('plugins.test')->isActive());
    }

    public function testShouldMonitorBasedOnTags()
    {

        Filesystem::create()->cleanDir($dirTag1 = self::$tmpDir.'/tag1');
        Filesystem::create()->cleanDir($dirTag2 = self::$tmpDir.'/tag2');

        Filesystem::create()->mkdir($dirTag1);
        Filesystem::create()->mkdir($dirTag2);
        $ftag1 = $dirTag1.'/test1.php';
        $ftag2 = $dirTag2.'/test1.php';
        $this->getTester()->run('--tags=tag1');
        file_put_contents($ftag1,'Hello World');
        file_put_contents($ftag2,'Hello WOrld');
        $this->evaluate();
        $this->assertContains($ftag1,$this->getDisplay());
        $this->assertNotContains($ftag2,$this->getDisplay());

        $this->getTester()->run('--tags=tag2');
        touch($ftag1 = $dirTag1.'/test2.php');
        touch($ftag2 = $dirTag2.'/test2.php');

        $this->evaluate();
        $this->assertContains($ftag2,$this->getDisplay());
        $this->assertNotContains($ftag1,$this->getDisplay());

        $this->getTester()->run('--tags=tag1,tag2');
        touch($ftag1 = $dirTag1.'/test3.php');
        touch($ftag2 = $dirTag2.'/test3.php');
        $this->evaluate();

        $this->assertContains($ftag2,$this->getDisplay());
        $this->assertContains($ftag1,$this->getDisplay());
    }

    public function testShouldCatchErrorWhenPluginThrowsAnError()
    {
        $this->getTester()->run('-vvv');
        TestPlugin::$throwException = true;
        file_put_contents($file=getcwd().'/tag1/test4.php','<?php',LOCK_EX);
        $this->evaluate();

        $this->assertDisplayContains(TestPlugin::THROW_MESSAGE);
    }

    public function testGetPlugincache()
    {
        $cacheDir = PhpGuard::getCacheDir();

        PhpGuard::getPluginCache('foo');
        $this->assertTrue(is_dir($cacheDir.'/plugins/foo'));
    }
}
