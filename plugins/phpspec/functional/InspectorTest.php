<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PhpSpec\Functional;

use PhpGuard\Application\Log\Logger;
use PhpGuard\Plugins\PhpSpec\Inspector;

/**
 * Class InspectorTest
 *
 * @package PhpGuard\Plugins\PhpSpec\Functional
 * @coversDefaultClass PhpGuard\Plugins\PhpSpec\Inspector
 */
class InspectorTest extends TestCase
{
    /**
     * @var Inspector
     */
    protected $inspector;

    protected function setUp()
    {
        parent::setUp();
        $container = static::$container;
        $phpspec = $container->get('plugins.phpspec');
        $logger = new Logger('Inspector');
        $logger->pushHandler($container->get('logger.handler'));

        $inspector = new Inspector();
        $inspector->setContainer($container);
        $inspector->setLogger($logger);
        $inspector->setOptions($phpspec->getOptions());

        $this->inspector = $inspector;
        $this->getTester()->run('-vvv');
    }

    public function testShouldRunWithClassName()
    {
        $inspector = $this->inspector;
        $inspector->runAll();
        // test
        $this->assertDisplayContains('3 passed');
    }

    /**
     * @covers ::runAll
     */
    public function testShouldKeepRunningFailedSpec()
    {
        $inspector = $this->inspector;
        $this->createSpecFile('spec/PhpSpecTest1/FooSpec.php','spec\\PhpSpecTest1','FooSpec');
        $this->createSpecFile('spec/PhpSpecTest1/BarSpec.php','spec\\PhpSpecTest1','BarSpec');

        //$this->getTester()->resetDisplay();

        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertDisplayContains('2 broken');

        // clear display
        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertNotDisplayContains('TestClass');
        $this->assertDisplayContains('Foo');
        $this->assertDisplayContains('Bar');

        unlink(getcwd().'/spec/PhpSpecTest1/BarSpec.php');
        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertNotDisplayContains('TestClass');
        $this->assertDisplayContains('Foo');
        $this->assertNotDisplayContains('Bar');

        $plugin = $this->getApplication()->getContainer()->get('plugins.phpspec');
        $options = $plugin->getOptions();

        $options['keep_failed'] = false;
        $plugin->setOptions($options);
        $this->getTester()->run('-vvv');
        $inspector->runAll();
        $this->assertNotDisplayContains('Bar');
        $this->assertDisplayContains('Foo');
        $this->assertDisplayContains('3 passed');
        $this->assertDisplayContains('1 broken');
    }
}
