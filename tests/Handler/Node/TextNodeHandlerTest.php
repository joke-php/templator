<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Node;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
 */
final class TextNodeHandlerTest extends TestCase
{
    private static TextNodeHandler $handler;
    private static DefaultCompiler $compiler;
    private static DefaultRenderer $renderer;

    public static function setUpBeforeClass(): void
    {
        self::$handler = new TextNodeHandler();
        self::$compiler = self::getStubBuilder(DefaultCompiler::class)
            ->disableOriginalConstructor()
            ->getStub();
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    #[DataProvider('provideHandlerCases')]
    public function testHandler(string $content): void
    {
        $node = new TextNode(TextToken::class, $content);
        $context = ['test' => 1];
        self::assertSame($content, self::$handler->render($node, self::$renderer, $context));
        self::assertSame($content, self::$handler->compile($node, self::$compiler, $context, ['test']));
    }

    public static function provideHandlerCases(): iterable
    {
        yield [' '];
        yield ['{{test}}'];
    }

    public function testRenderException(): void
    {
        $node = new BlockNode(TextToken::class, 'test', '');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage('Expected instance of TextNode, got Vasoft\Joke\Templator\Parser\Node\BlockNode.');
        self::$handler->render($node, self::$renderer, []);
    }

    public function testCompileException(): void
    {
        $node = new BlockNode(TextToken::class, 'test', '');
        self::expectException(CompileException::class);
        self::expectExceptionMessage('Expected instance of TextNode, got Vasoft\Joke\Templator\Parser\Node\BlockNode.');
        self::$handler->compile($node, self::$renderer, []);
    }
}
