<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Functional\Console;

use PhpGuard\Application\Functional\TestCase;
use PhpGuard\Application\Functional\TestPlugin;

class ShellTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->getTester()->run();
    }

    public function testShouldEvaluateChange()
    {
        touch($file1 = static::$tmpDir.'/src/PhpGuardTest/Namespace1/NewClass.php');

        $this->evaluate();
        $this->assertContains($file1,$this->getDisplay());
    }

    public function testShouldRenderExceptionIfPluginThrowsExceptionWhenRunning()
    {

        TestPlugin::$throwException = true;
        touch(self::$tmpDir.'/src/PhpGuardTest/Namespace1/TestThrow.php');
        $this->getTester()->run('all test');
        $this->assertContains(TestPlugin::THROW_MESSAGE,$this->getDisplay());
    }
}
