<?php

namespace Phpactor\Tests\System\Console\Command;

use Phpactor\Tests\System\SystemTestCase;

class StatusCommandTest extends SystemTestCase
{
    public function testStatus()
    {
        $process = $this->phpactor('status');
        $this->assertSuccess($process);
    }
}
