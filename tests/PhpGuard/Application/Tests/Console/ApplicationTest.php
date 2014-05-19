<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Application\Tests\Console;

use PhpGuard\Application\Tests\FunctionalTestCase;

class ApplicationTest extends FunctionalTestCase
{
    public function testShouldStartShellOnRunning()
    {
        $tester = $this->getApplicationTester();
        $exit = $tester->run(array());
        $display = $tester->getDisplay(true);
        $this->assertEquals(0,$exit);
        $this->assertContains('Welcome',$display);
    }

    public function testShouldRunSomeBasicCommand()
    {
        $tester = $this->getApplicationTester();

        $exit = $tester->run(array('help'));
        $display = $tester->getDisplay();

        $this->assertEquals(0,$exit);
        $this->assertContains('Usage:',$display);

        $exit = $tester->run(array('list'));
        $display = $tester->getDisplay();

        $this->assertEquals(0,$exit);
        $this->assertContains('phpguard version',$display);
    }
}