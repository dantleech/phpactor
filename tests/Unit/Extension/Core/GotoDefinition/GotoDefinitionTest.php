<?php

namespace Phpactor\Tests\Unit\Extension\Core\GotoDefinition;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;
use Phpactor\WorseReflection\Reflector;
use Phpactor\Extension\WorseReflection\GotoDefinition\GotoDefinition;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Position;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMethodCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\Extension\WorseReflection\GotoDefinition\GotoDefinitionResult;
use Phpactor\WorseReflection\Core\Reflection\ReflectionConstant;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionConstantCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionProperty;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionPropertyCollection;
use Phpactor\Extension\WorseReflection\GotoDefinition\Exception\GotoDefinitionException;

class GotoDefinitionTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $reflector;
    /**
     * @var GotoDefinition
     */
    private $action;

    /**
     * @var ObjectProphecy
     */
    private $reflectionClass;

    /**
     * @var ObjectProphecy
     */
    private $reflectionMethodCollection;

    /**
     * @var ObjectProphecy
     */
    private $reflectionMethod;

    public function setUp()
    {
        $this->reflector = $this->prophesize(Reflector::class);

        $this->action = new GotoDefinition($this->reflector->reveal());

        $this->reflectionClass = $this->prophesize(ReflectionClass::class);
        $this->reflectionFunction = $this->prophesize(ReflectionFunction::class);

        $this->reflectionMethod = $this->prophesize(ReflectionMethod::class);
        $this->reflectionMethodCollection = $this->prophesize(ReflectionMethodCollection::class);

        $this->reflectionConstant = $this->prophesize(ReflectionConstant::class);
        $this->reflectionConstantCollection = $this->prophesize(ReflectionConstantCollection::class);

        $this->reflectionProperty = $this->prophesize(ReflectionProperty::class);
        $this->reflectionPropertyCollection = $this->prophesize(ReflectionPropertyCollection::class);
    }

    /**
     * It fails if it doesn't know how to resolve an action.
     */
    public function testUnresolvableSymbol()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('Do not know how to goto definition of symbol');

        $info = SymbolContext::for(Symbol::unknown());

        $this->action->gotoDefinition($info);
    }

    /**
     * Method: It fails if the containing class cannot be determined.
     */
    public function testNoContainingClass()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('Containing class for member "aaa" could not be determined');

        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::METHOD, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $result = $this->action->gotoDefinition($info);
    }

    /**
     * Method: It fails if the contianing class is not found.
     */
    public function testContainingClassNotFound()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('Notfound');

        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::METHOD, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withContainerType(Type::fromString('Foobar'));
        $this->reflector->reflectClassLike(ClassName::fromString('Foobar'))->willThrow(new NotFound('Notfound'));

        $result = $this->action->gotoDefinition($info);
    }

    /**
     * Method: It fails if the class has no path associated with it.
     */
    public function testClassNoPath()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('The source code for class "asd" has no path associated with it');

        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::METHOD, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withContainerType(Type::fromString('Foobar'));
        $this->reflector->reflectClassLike(ClassName::fromString('Foobar'))->willReturn($this->reflectionClass->reveal());
        $this->reflectionClass->sourceCode()->willReturn(SourceCode::fromString('asd'));
        $this->reflectionClass->name()->willReturn(ClassName::fromString('asd'));

        $result = $this->action->gotoDefinition($info);
    }

    public function testGotoDefinitionFunctionNoSourceCode()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('The source code for function "asd" has no path associated with it');
        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::FUNCTION, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withName(Name::fromString('Foobar'));
        $this->reflector->reflectFunction(Name::fromString('Foobar'))->willReturn($this->reflectionFunction->reveal());
        $this->reflectionFunction->sourceCode()->willReturn(SourceCode::fromString('asd'));
        $this->reflectionFunction->name()->willReturn(Name::fromString('asd'));

        $this->action->gotoDefinition($info);
    }

    public function testGotoDefinitionFunction()
    {
        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::FUNCTION, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withName(Name::fromString('Foobar'));
        $this->reflector->reflectFunction(Name::fromString('Foobar'))->willReturn($this->reflectionFunction->reveal());
        $this->reflectionFunction->sourceCode()->willReturn(SourceCode::fromPath(__FILE__));
        $this->reflectionFunction->name()->willReturn(Name::fromString('asd'));
        $this->reflectionFunction->position()->willReturn(Position::fromStartAndEnd(10, 20));

        $result = $this->action->gotoDefinition($info);
        $this->assertEquals(10, $result->offset());
        $this->assertEquals(__FILE__, $result->path());
    }

    /**
     * Method: It fails if the containing class does not have the method.
     */
    public function testMethodNotFound()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('Class "class1" has no method named "aaa", has: "a", "b", "c"');

        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::METHOD, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withContainerType(Type::fromString('Foobar'));
        $this->reflector->reflectClassLike(ClassName::fromString('Foobar'))->willReturn($this->reflectionClass->reveal());
        $this->reflectionClass->name()->willReturn(ClassName::fromString('class1'));
        $this->reflectionClass->methods()->willReturn($this->reflectionMethodCollection->reveal());
        $this->reflectionClass->sourceCode()->willReturn(SourceCode::fromPath(__FILE__));
        $this->reflectionMethodCollection->has('aaa')->willReturn(false);
        $this->reflectionMethodCollection->keys()->willReturn(['a', 'b', 'c']);

        $result = $this->action->gotoDefinition($info);
    }

    /**
     * Method: It returns the gotodefinition result.
     */
    public function testGotoDefinition()
    {
        $this->reflectionClass->methods()->willReturn($this->reflectionMethodCollection->reveal());
        $this->reflectionMethodCollection->has('aaa')->willReturn(true);
        $this->reflectionMethodCollection->get('aaa')->willReturn($this->reflectionMethod->reveal());
        $this->reflectionMethod->position()->willReturn(Position::fromStartAndEnd(10, 20));

        $this->assertGotoDefinition(Symbol::METHOD);
    }

    /**
     * Contstant: It returns the gotodefinition result.
     */
    public function testGotoDefinitionConstnat()
    {
        $this->reflectionClass->constants()->willReturn($this->reflectionConstantCollection->reveal());
        $this->reflectionConstantCollection->has('aaa')->willReturn(true);
        $this->reflectionConstantCollection->get('aaa')->willReturn($this->reflectionConstant->reveal());
        $this->reflectionConstant->position()->willReturn(Position::fromStartAndEnd(10, 20));

        $this->assertGotoDefinition(Symbol::CONSTANT);
    }

    /**
     * Property: It returns the gotodefinition result.
     */
    public function testGotoDefinitionProperty()
    {
        $this->reflectionClass->properties()->willReturn($this->reflectionPropertyCollection->reveal());
        $this->reflectionPropertyCollection->has('aaa')->willReturn(true);
        $this->reflectionPropertyCollection->get('aaa')->willReturn($this->reflectionProperty->reveal());
        $this->reflectionProperty->position()->willReturn(Position::fromStartAndEnd(10, 20));
        $this->reflectionClass->isInterface()->willReturn(false);

        $this->assertGotoDefinition(Symbol::PROPERTY);
    }

    /**
     * Property: Fails if the class is an interface.
     */
    public function testGotoDefinitionPropertyIsInterface()
    {
        $this->expectException(GotoDefinitionException::class);
        $this->expectExceptionMessage('Symbol is a property and class "class1" is an interface');

        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition(Symbol::PROPERTY, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withContainerType(Type::fromString('Foobar'));
        $this->reflector->reflectClassLike(ClassName::fromString('Foobar'))->willReturn($this->reflectionClass->reveal());
        $this->reflectionClass->isInterface()->willReturn(true);
        $this->reflectionClass->name()->willReturn(ClassName::fromString('class1'));

        $result = $this->action->gotoDefinition($info);
    }

    private function assertGotoDefinition($symbolType)
    {
        $info = SymbolContext::for(
            Symbol::fromTypeNameAndPosition($symbolType, 'aaa', Position::fromStartAndEnd(1, 2))
        );
        $info = $info->withContainerType(Type::fromString('Foobar'));
        $this->reflector->reflectClassLike(ClassName::fromString('Foobar'))->willReturn($this->reflectionClass->reveal());
        $this->reflectionClass->name()->willReturn(ClassName::fromString('class1'));
        $this->reflectionClass->sourceCode()->willReturn(SourceCode::fromPath(__FILE__));

        $result = $this->action->gotoDefinition($info);

        $this->assertEquals(
            GotoDefinitionResult::fromClassPathAndOffset(__FILE__, 10),
            $result
        );
    }
}
