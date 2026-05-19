<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Node;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
 */
final class PrintNodeHandlerTest extends TestCase
{
    private static DefaultRenderer $renderer;

    public static function setUpBeforeClass(): void
    {
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    public function testCompileFromContext(): void
    {
        $handler = new PrintNodeHandler(new TemplatorConfig());
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= htmlspecialchars((string)\$context['test'], ENT_QUOTES, 'UTF-8');?>",
            $handler->compile($node, self::$renderer, $context),
        );
    }

    public function testCompileFromContextEncoding(): void
    {
        $config = new TemplatorConfig()->setEncoding('windows-1251');
        $handler = new PrintNodeHandler($config);
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= htmlspecialchars((string)\$context['test'], ENT_QUOTES, 'windows-1251');?>",
            $handler->compile($node, self::$renderer, $context),
        );
    }

    public function testRender(): void
    {
        $handler = new PrintNodeHandler(new TemplatorConfig());
        $node = new PrintNode('test');
        $context = ['test' => '<script>'];
        self::assertSame(
            '&lt;script&gt;',
            $handler->render($node, self::$renderer, $context),
        );
    }

    public function testCompileFromLocal(): void
    {
        $handler = new PrintNodeHandler(new TemplatorConfig());
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= htmlspecialchars((string)\$test, ENT_QUOTES, 'UTF-8');?>",
            $handler->compile($node, self::$renderer, $context, ['test']),
        );
    }

    public function testCompileFromLocalEncoding(): void
    {
        $config = new TemplatorConfig()->setEncoding('windows-1251');
        $handler = new PrintNodeHandler($config);
        $node = new PrintNode('test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= htmlspecialchars((string)\$test, ENT_QUOTES, 'windows-1251');?>",
            $handler->compile($node, self::$renderer, $context, ['test']),
        );
    }

    public function testRenderException(): void
    {
        $handler = new PrintNodeHandler(new TemplatorConfig());
        $node = new BlockNode('test', '');
        self::expectException(RenderingException::class);
        self::expectExceptionMessage(
            'Expected instance of PrintNode, got Vasoft\Joke\Templator\Parser\Node\BlockNode.',
        );
        $handler->render($node, self::$renderer, []);
    }

    public function testCompileException(): void
    {
        $handler = new PrintNodeHandler(new TemplatorConfig());
        $node = new BlockNode('test', '');
        self::expectException(CompileException::class);
        self::expectExceptionMessage(
            'Expected instance of PrintNode, got Vasoft\Joke\Templator\Parser\Node\BlockNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }
}
