<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Directive;

use PHPUnit\Framework\MockObject\Stub;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Handler\Directive\EachHandler;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Directive\EachHandler
 */
final class EachHandlerTest extends TestCase
{
    private static Stub|DefaultRenderer $renderer;
    private static Stub|DefaultCompiler $compiler;

    public static function setUpBeforeClass(): void
    {
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
        self::$compiler = self::getStubBuilder(DefaultCompiler::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    public function testCompileFromContext(): void
    {
        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'item in list');
        $context = ['list' => [1, 2]];
        self::assertSame(
            "<?php foreach (\$context['list'] as \$item): ?><?php endforeach; ?>",
            $handler->compile($node, self::$compiler, $context),
        );
    }

    public function testCompileFromLocal(): void
    {
        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'item in list');
        $context = ['list' => [1, 2]];
        self::assertSame(
            '<?php foreach ($list as $item): ?><?php endforeach; ?>',
            $handler->compile($node, self::$compiler, $context, ['list']),
        );
    }

    public function testCompileLocalAdded(): void
    {
        /** @var DefaultCompiler|Stub $compiler */
        $compiler = self::getMockBuilder(DefaultCompiler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $compiler->expects(self::exactly(1))
            ->method('process')
            ->willReturnCallback(
                static fn(array $ast, array $context, array $localVars = []): string => implode(
                    ',',
                    array_map(static fn($node) => $node, $localVars),
                ),
            );

        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'id, item in list');
        $node->addChild(new PrintNode(PrintToken::class, 'test'));
        $context = ['list' => [1, 2]];
        self::assertSame(
            '<?php foreach ($list as $id => $item): ?>item,id<?php endforeach; ?>',
            $handler->compile($node, $compiler, $context, ['list']),
        );
    }

    public function testCompileExceptionNodeType(): void
    {
        $handler = new EachHandler();
        $node = new TextNode(TextToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessage(
            'Expected instance of BlockNode, got Vasoft\Joke\Templator\Parser\Node\TextNode.',
        );
        $handler->compile($node, self::$compiler, []);
    }

    public function testCompileExceptionSyntax(): void
    {
        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessage('Invalid foreach syntax: \'test\'.');
        $handler->compile($node, self::$compiler, []);
    }

    public function testCompileExceptionSyntaxIn(): void
    {
        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'test in ');
        self::expectException(CompileException::class);
        self::expectExceptionMessage('Invalid foreach syntax: \'test in \'.');
        $handler->compile($node, self::$compiler, []);
    }

    public function testRenderExceptionNodeType(): void
    {
        $handler = new EachHandler();
        $node = new TextNode(PrintToken::class, 'test');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage(
            'Expected instance of BlockNode, got Vasoft\Joke\Templator\Parser\Node\TextNode.',
        );
        $handler->render($node, self::$renderer, []);
    }

    public function testRenderNoCases(): void
    {
        /** @var DefaultRenderer|NodeProcessorInterface|Stub $compiler */
        $renderer = self::getMockBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $renderer->expects(self::never())->method('process');
        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'id, item in list');
        $context = ['list' => []];
        self::assertSame('', $handler->render($node, $renderer, $context));
    }

    public function testRender(): void
    {
        /** @var DefaultRenderer|NodeProcessorInterface|Stub $compiler */
        $renderer = self::getMockBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $renderer->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(
                static fn(array $ast, array $context, array $localVars = []): string => implode(
                    '',
                    array_map(static fn($item) => $context[$item->content] ?? '', $ast),
                ),
            );
        $handler = new EachHandler();
        $node = new BlockNode(StatementToken::class, 'each', 'id, item in list');
        $node->addChild(new PrintNode(PrintToken::class, 'id'));
        $node->addChild(new PrintNode(PrintToken::class, 'separator'));
        $node->addChild(new PrintNode(PrintToken::class, 'item'));
        $node->addChild(new PrintNode(PrintToken::class, 'separator'));
        $context = ['list' => ['a', 'b'], 'separator' => ':'];
        self::assertSame('0:a:1:b:', $handler->render($node, $renderer, $context));
    }
}
