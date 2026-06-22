<?php

declare(strict_types=1);

namespace Handler\Node;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\TemplatorConfig;
use PHPUnit\Framework\MockObject\Stub;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler
 */
final class StatementNodeHandlerTest extends TestCase
{
    private static NodeProcessorInterface|Stub $processor;
    private static NodeHandler|Stub $handler;

    public static function setUpBeforeClass(): void
    {
        self::$handler = self::getStubBuilder(NodeHandler::class)->getStub();
        self::$handler->method('compile')->willReturn('compiled');
        self::$handler->method('render')->willReturn('rendered');

        self::$processor = self::getStubBuilder(NodeProcessorInterface::class)->getStub();
    }

    public function testCompile(): void
    {
        $config = new TemplatorConfig();
        /** @var ServiceContainer $container */
        $container = self::getMockBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects(self::once())
            ->method('has')
            ->willReturn(true);
        $container->expects(self::once())
            ->method('get')
            ->willReturn(self::$handler);

        $handler = new StatementNodeHandler($container, $config);
        $result = $handler->compile(new BlockNode(StatementToken::class, 'test', ''), self::$processor, []);
        self::assertSame('compiled', $result);
    }

    public function testRender(): void
    {
        $config = new TemplatorConfig();
        /** @var ServiceContainer $container */
        $container = self::getMockBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects(self::once())
            ->method('has')
            ->willReturn(true);
        $container->expects(self::once())
            ->method('get')
            ->willReturn(self::$handler);

        $handler = new StatementNodeHandler($container, $config);
        $result = $handler->render(new BlockNode(StatementToken::class, 'test', ''), self::$processor, []);
        self::assertSame('rendered', $result);
    }

    public function testInitHandlerOnce(): void
    {
        $config = new TemplatorConfig();
        /** @var ServiceContainer $container */
        $container = self::getMockBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects(self::once())
            ->method('has')
            ->willReturn(false);
        $container->expects(self::once())
            ->method('get')
            ->willReturn(self::$handler);

        $handler = new StatementNodeHandler($container, $config);
        $config->addDirectiveHandler('test', StatementNodeHandler::class);
        $handler->compile(new BlockNode(StatementToken::class, 'test', ''), self::$processor, []);
        $result = $handler->compile(new BlockNode(StatementToken::class, 'test', ''), self::$processor, []);
        self::assertSame('compiled', $result);
    }

    public function testRenderExceptionNodeType(): void
    {
        $config = new TemplatorConfig();
        /** @var ServiceContainer $container */
        $container = self::getMockBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects(self::never())->method('has');
        $container->expects(self::never())->method('get');

        $handler = new StatementNodeHandler($container, $config);

        self::expectException(RenderingException::class);
        self::expectExceptionMessage(
            'Expected instance of Vasoft\Joke\Templator\Parser\Node\StatementNode, got Vasoft\Joke\Templator\Parser\Node\TextNode.',
        );
        $handler->render(new TextNode(TextToken::class, 'test'), self::$processor, []);
    }

    public function testCompileExceptionNodeType(): void
    {
        $config = new TemplatorConfig();
        /** @var ServiceContainer $container */
        $container = self::getMockBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects(self::never())->method('has');
        $container->expects(self::never())->method('get');

        $handler = new StatementNodeHandler($container, $config);

        self::expectException(CompileException::class);
        self::expectExceptionMessage(
            'Expected instance of Vasoft\Joke\Templator\Parser\Node\StatementNode, got Vasoft\Joke\Templator\Parser\Node\TextNode.',
        );
        $handler->compile(new TextNode(TextToken::class, 'test'), self::$processor, []);
    }
}
