<?php

namespace Phpactor\Tests\Unit\Rpc\Handler;

use Phpactor\Rpc\Handler;
use Phpactor\Rpc\Handler\ContextMenuHandler;
use Phpactor\WorseReflection\Reflector;
use PhpBench\DependencyInjection\Container;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\Rpc\Editor\EchoAction;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\Rpc\Editor\InputCallbackAction;
use Phpactor\Rpc\ActionRequest;
use Phpactor\Container\RpcExtension;
use Phpactor\Rpc\RequestHandler\RequestHandler;
use Phpactor\Rpc\Request;
use Phpactor\Rpc\Response;
use Phpactor\Application\Helper\ClassFileNormalizer;

class ContextMenuHandlerTest extends HandlerTestCase
{
    const VARIABLE_ACTION = 'do_something';
    const SOURCE = '<?php $hello = "world"; echo $hello;';

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $menu = [];

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * @var ClassFileNormalizer
     */
    private $classFileNormalizer;

    public function setUp()
    {
        $this->reflector = Reflector::create(new StringSourceLocator(SourceCode::fromPath(__FILE__)));
        $this->classFileNormalizer = $this->prophesize(ClassFileNormalizer::class);
        $this->container = $this->prophesize(Container::class);
        $this->requestHandler = $this->prophesize(RequestHandler::class);
    }

    public function createHandler(): Handler
    {
        return new ContextMenuHandler(
            $this->reflector,
            $this->classFileNormalizer->reveal(),
            $this->menu,
            $this->container->reveal()
        );
    }

    public function testNoActionsAvailable()
    {
        $action = $this->handle(ContextMenuHandler::NAME, [
            'source' => '<?php $hello = "world"; echo $hello;',
            'offset' => 4,
        ]);

        $this->assertInstanceOf(EchoAction::class, $action);
        $this->assertContains('No context actions', $action->message());
    }

    public function testReturnMenu()
    {
        $this->menu = [
            Symbol::VARIABLE => [
                'action' => self::VARIABLE_ACTION,
                'parameters' => [
                    'one' => 1,
                ],
            ]
        ];
        $action = $this->handle(ContextMenuHandler::NAME, [
            'source' => '<?php $hello = "world"; echo $hello;',
            'offset' => 8,
        ]);

        $this->assertInstanceOf(InputCallbackAction::class, $action);
        $this->assertInstanceOf(ActionRequest::class, $action->callbackAction());
        $this->assertEquals(ContextMenuHandler::NAME, $action->callbackAction()->name());
    }

    public function testReplaceTokens()
    {
        $this->container->get(RpcExtension::SERVICE_REQUEST_HANDLER)->willReturn(
            $this->requestHandler->reveal()
        );

        $this->classFileNormalizer->classToFile('string')->willReturn(__FILE__);

        $this->requestHandler->handle(Request::fromActions([
            ActionRequest::fromNameAndParameters(
                self::VARIABLE_ACTION,
                [
                    'some_source' => self::SOURCE,
                    'some_offset' => 8,
                    'some_path' => __FILE__
                ]
            )
        ]))->willReturn(
            Response::fromActions([
                EchoAction::fromMessage('Hello')
            ])
        );

        $this->menu = [
            Symbol::VARIABLE => [
                self::VARIABLE_ACTION => [
                    'action' => self::VARIABLE_ACTION,
                    'parameters' => [
                        'some_source' => '%source%',
                        'some_offset' => '%offset%',
                        'some_path' => '%path%',
                    ],
                ],
            ]
        ];

        $action = $this->handle(ContextMenuHandler::NAME, [
            'action' => self::VARIABLE_ACTION,
            'source' => self::SOURCE,
            'offset' => 8,
        ]);

        $actions = $action->actions();
        $action = reset($actions);
        $parameters = $action->parameters();
        $this->assertEquals([
            'message' => 'Hello',
        ], $parameters);
    }
}
