<?php

namespace Phpactor\Tests\Unit\Extension\ClassMover\Rpc;

use Phpactor\Rpc\Handler;
use Phpactor\Extension\ClassMover\Application\ClassCopy;
use Phpactor\Rpc\Response\InputCallbackResponse;
use Phpactor\Extension\ClassMover\Rpc\ClassCopyHandler;
use Phpactor\Rpc\Request;
use Prophecy\Argument;
use Phpactor\Rpc\Response\OpenFileResponse;
use Phpactor\Rpc\Response\Input\TextInput;
use Phpactor\Extension\ClassMover\Application\NullLogger;
use Phpactor\Tests\Unit\Rpc\Handler\HandlerTestCase;

class ClassCopyHandlerTest extends HandlerTestCase
{
    const SOURCE_PATH = 'souce_path';
    const DEST_PATH = 'souce_path';

    /**
     * @var ClassCopy
     */
    private $classCopy;

    public function setUp()
    {
        $this->classCopy = $this->prophesize(ClassCopy::class);
    }

    public function createHandler(): Handler
    {
        return new ClassCopyHandler(
            $this->classCopy->reveal()
        );
    }


    /**
     * @testdox It should request the dest path if none is given.
     */
    public function testNoDestPath()
    {
        /** @var $action InputCallbackAction */
        $action = $this->handle('copy_class', [
            'source_path' => self::SOURCE_PATH,
            'dest_path' => null,
        ]);

        $this->assertInstanceOf(InputCallbackResponse::class, $action);
        $inputs = $action->inputs();
        $this->assertCount(1, $inputs);
        $this->assertInstanceOf(TextInput::class, reset($inputs));
        $this->assertInstanceOf(Request::class, $action->callbackAction());
        $this->assertEquals('copy_class', $action->callbackAction()->name());
        $this->assertEquals([
            'source_path' => self::SOURCE_PATH,
            'dest_path' => null,
        ], $action->callbackAction()->parameters());
    }

    public function testCopyClass()
    {
        $this->classCopy->copy(
            Argument::type(NullLogger::class),
            self::SOURCE_PATH,
            self::DEST_PATH
        )->shouldBeCalled();

        /** @var $action InputCallbackAction */
        $action = $this->handle('copy_class', [
            'source_path' => self::SOURCE_PATH,
            'dest_path' => self::DEST_PATH,
        ]);

        $this->assertInstanceOf(OpenFileResponse::class, $action);
    }
}
