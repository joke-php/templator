<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Node;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
 */
final class PrintNodeHandlerTest extends TestCase
{
    private static PrintNodeHandler $handler;
    private static DefaultCompiler $compiler;
    private static DefaultRenderer $renderer;

    public static function setUpBeforeClass(): void
    {
        self::$handler = new PrintNodeHandler();
        self::$compiler = self::getStubBuilder(DefaultCompiler::class)
            ->disableOriginalConstructor()
            ->getStub();
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    public function testCompileFromContext(): void
    {
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= htmlspecialchars((string)\$context['test'], ENT_QUOTES, 'UTF-8');?>",
            self::$handler->compile($node, self::$renderer, $context),
        );
    }

    public function testRender(): void
    {
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            '1',
            self::$handler->render($node, self::$renderer, $context),
        );
    }

    public function testCompileFromLocal(): void
    {
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= htmlspecialchars((string)\$test, ENT_QUOTES, 'UTF-8');?>",
            self::$handler->compile($node, self::$renderer, $context, ['test']),
        );
    }

    public function testRenderException(): void
    {
        $node = new BlockNode('test', '');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage(
            'Expected instance of PrintNode, got Vasoft\Joke\Templator\Parser\Node\BlockNode.',
        );
        self::$handler->render($node, self::$renderer, []);
    }

    public function testCompileException(): void
    {
        $node = new BlockNode('test', '');
        self::expectException(CompileException::class);
        self::expectExceptionMessage(
            'Expected instance of PrintNode, got Vasoft\Joke\Templator\Parser\Node\BlockNode.',
        );
        self::$handler->compile($node, self::$renderer, []);
    }
}
