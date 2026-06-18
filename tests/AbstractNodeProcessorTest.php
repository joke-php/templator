<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use Vasoft\Joke\Templator\AbstractNodeProcessor;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\AbstractNodeProcessor
 */
final class AbstractNodeProcessorTest extends ProcessorTestBase
{
    public function testRender(): void
    {
        /** @var AbstractNodeProcessor $processor */
        $processor = self::getMockBuilder(AbstractNodeProcessor::class)
            ->setConstructorArgs([$this->container, $this->config])
            ->onlyMethods(['getHandlerMethodName'])
            ->getMock();
        $processor
            ->expects(self::exactly(14))
            ->method('getHandlerMethodName')
            ->willReturn('render');

        $expected = <<<'text'
            testVariableValue
            single text
            branch1
            0:a
            1:b

            text;

        self::assertSame(
            $expected,
            $processor->process($this->getDefaultAst(), $this->getDefaultContext(), ['test' => '=']),
        );
    }

    public function testInvalidHandler(): void
    {
        $this->container->registerSingleton(PrintNode::class . '#Handler', new \stdClass());
        /** @var AbstractNodeProcessor $processor */
        $processor = self::getMockBuilder(AbstractNodeProcessor::class)
            ->setConstructorArgs([$this->container, $this->config])
            ->onlyMethods(['getHandlerMethodName'])
            ->getMock();
        $processor
            ->expects(self::never())
            ->method('getHandlerMethodName');

        self::expectException(TemplatorException::class);
        self::expectExceptionMessage(
            "Invalid node handler 'stdClass' for 'Vasoft\\Joke\\Templator\\Parser\\Node\\PrintNode'.",
        );
        $processor->process($this->getDefaultAst(), $this->getDefaultContext());
    }
}
