<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Directive;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\Directive\IfHandler;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Directive\IfHandler;
 */
final class IfHandlerTest extends TestCase
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

    public function testCompileIfFromContext(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $context = ['test' => true];
        self::assertSame(
            "<?php if((bool)(\$context['test'])): ?><?php endif; ?>",
            $handler->compile($node, self::$compiler, $context),
        );
    }

    public function testCompileIfFromLocal(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $context = ['test' => true];
        self::assertSame(
            '<?php if((bool)($test)): ?><?php endif; ?>',
            $handler->compile($node, self::$compiler, $context, ['test']),
        );
    }

    public function testCompileElseIfFromContext(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $node->openBranch('elseif', 'test2');
        $context = [];
        self::assertSame(
            "<?php if((bool)(\$test)): ?><?php elseif((bool)(\$context['test2'])): ?><?php endif; ?>",
            $handler->compile($node, self::$compiler, $context, ['test']),
        );
    }

    public function testCompileElseIfFromLocal(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $node->openBranch('elseif', 'test2');
        $context = [];
        self::assertSame(
            '<?php if((bool)($test)): ?><?php elseif((bool)($test2)): ?><?php endif; ?>',
            $handler->compile($node, self::$compiler, $context, ['test', 'test2']),
        );
    }

    public function testCompile(): void
    {
        /** @var DefaultCompiler|Stub $compiler */
        $compiler = self::getMockBuilder(DefaultCompiler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $compiler->expects(self::exactly(4))
            ->method('process')
            ->willReturnCallback(
                static fn(array $ast, array $context, array $localVars = []): string => $ast[0]->content,
            );
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $node->addChild(new TextNode(TextToken::class, 'if-branch'));
        $node->openBranch('elseif', 'test2');
        $node->addChild(new TextNode(TextToken::class, 'elseif-1-branch'));
        $node->openBranch('elseif', 'test3');
        $node->addChild(new TextNode(TextToken::class, 'elseif-2-branch'));
        $node->openBranch('else');
        $node->addChild(new TextNode(TextToken::class, 'else-branch'));
        $context = [];
        self::assertSame(
            '<?php if((bool)($test)): ?>if-branch<?php elseif((bool)($test2)): ?>elseif-1-branch<?php elseif((bool)($context[\'test3\'])): ?>elseif-2-branch<?php else: ?>else-branch<?php endif; ?>',
            $handler->compile($node, $compiler, $context, ['test', 'test2']),
        );
    }

    public function testCompileException(): void
    {
        $handler = new IfHandler();
        $node = new TextNode(TextToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessage(
            'Expected instance of BlockNode, got Vasoft\Joke\Templator\Parser\Node\TextNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }

    public function testCompileIfWithoutExpressionException(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', '');
        self::expectException(CompileException::class);
        self::expectExceptionMessage('Directive \'if\' with no arguments.');
        $handler->compile($node, self::$compiler, []);
    }

    public function testCompileElseIfWithoutExpressionException(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $node->openBranch('elseif');
        self::expectException(CompileException::class);
        self::expectExceptionMessage('Directive \'elseif\' with no arguments.');
        $handler->compile($node, self::$compiler, []);
    }

    public function testRenderNoCases(): void
    {
        /** @var DefaultRenderer|NodeProcessorInterface|Stub $compiler */
        $renderer = self::getMockBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $renderer->expects(self::never())->method('process');
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test1');
        $context = ['test1' => false];
        self::assertSame('', $handler->render($node, $renderer, $context));
    }

    #[DataProvider('provideRenderCases')]
    public function testRender(bool $test1, bool $test2, bool $test3, string $expected): void
    {
        /** @var DefaultRenderer|NodeProcessorInterface|Stub $compiler */
        $renderer = self::getMockBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $renderer->expects(self::exactly(1))
            ->method('process')
            ->willReturnCallback(
                static fn(array $ast, array $context, array $localVars = []): string => $ast[0]->content,
            );
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test1');
        $node->addChild(new TextNode(TextToken::class, 'if-branch'));
        $node->openBranch('elseif', 'test2');
        $node->addChild(new TextNode(TextToken::class, 'elseif-1-branch'));
        $node->openBranch('elseif', 'test3');
        $node->addChild(new TextNode(TextToken::class, 'elseif-2-branch'));
        $node->openBranch('else');
        $node->addChild(new TextNode(TextToken::class, 'else-branch'));
        $context = ['test1' => $test1, 'test2' => $test2, 'test3' => $test3];
        self::assertSame($expected, $handler->render($node, $renderer, $context));
    }

    public static function provideRenderCases(): iterable
    {
        yield [true, true, true, 'if-branch'];
        yield [false, true, true, 'elseif-1-branch'];
        yield [false, false, true, 'elseif-2-branch'];
        yield [false, false, false, 'else-branch'];
    }

    public function testRenderException(): void
    {
        $handler = new IfHandler();
        $node = new TextNode(TextToken::class, 'test');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage(
            'Expected instance of BlockNode, got Vasoft\Joke\Templator\Parser\Node\TextNode.',
        );
        $handler->render($node, self::$renderer, []);
    }

    public function testRenderIfWithoutExpressionException(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', '');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage('Directive \'if\' with no arguments.');
        $handler->render($node, self::$compiler, []);
    }

    public function testRenderElseIfWithoutExpressionException(): void
    {
        $handler = new IfHandler();
        $node = new BlockNode(StatementToken::class, 'if', 'test');
        $node->openBranch('elseif');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage('Directive \'elseif\' with no arguments.');
        $handler->render($node, self::$compiler, []);
    }
}
