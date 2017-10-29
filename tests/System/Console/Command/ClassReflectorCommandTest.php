<?php

namespace Phpactor\Tests\System\Console\Command;

use Phpactor\Tests\System\SystemTestCase;

class ClassReflectorCommandTest extends SystemTestCase
{
    public function setUp()
    {
        $this->initWorkspace();
        $this->loadProject('Animals');
    }

    /**
     * @testdox Test reflection
     */
    public function testReflectCommand()
    {
        $process = $this->phpactor('class:reflect lib/Badger.php');
        $this->assertSuccess($process);
        $output = $process->getOutput();
        $this->assertContains('Animals\Badger', $output);
        $this->assertContains('methods', $output);
    }

    /**
     * @testdox Test for class
     */
    public function testReflectCommandWithClass()
    {
        $process = $this->phpactor('class:reflect "Animals\\Badger"');
        $this->assertSuccess($process);
        $output = $process->getOutput();
        $this->assertContains('Animals\Badger', $output);
        $this->assertContains('methods', $output);
    }
}
