<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Statement;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Stub;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Handler\Statement\RawHandler;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Statement\RawHandler
 */
#[CoversClass(RawHandler::class)]
#[TestDox('RawHandler - вывод необработанной строки')]
final class RawHandlerTest extends TestCase
{
    private static Stub|DefaultRenderer $renderer;

    public static function setUpBeforeClass(): void
    {
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    #[TestDox('Компилирует вывод значения из контекста')]
    public function testCompileFromContext(): void
    {
        $handler = new RawHandler();
        $node = new StatementNode(StatementToken::class, 'raw', 'test');
        $context = ['test' => 1];
        self::assertSame(
            "<?= \$context['test']?>",
            $handler->compile($node, self::$renderer, $context),
        );
    }

    #[TestDox('Рендер вывода значения без замены спец. символов')]
    public function testRender(): void
    {
        $handler = new RawHandler();
        $node = new StatementNode(StatementToken::class, 'raw', 'test');
        $context = ['test' => '<script>'];
        self::assertSame(
            '<script>',
            $handler->render($node, self::$renderer, $context),
        );
    }

    #[TestDox('Рендер вывода значения из локальной области без замены спец. символов')]
    public function testCompileFromLocal(): void
    {
        $handler = new RawHandler();
        $node = new StatementNode(StatementToken::class, 'raw', 'test');
        $context = ['test' => 1];
        self::assertSame(
            '<?= $test?>',
            $handler->compile($node, self::$renderer, $context, ['test']),
        );
    }

    #[TestDox('Рендер выбрасывает исключение если получено не StatementNode')]
    public function testRenderException(): void
    {
        $handler = new RawHandler();
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs(
            'Expected instance of StatementNode, got Vasoft\Joke\Templator\Parser\Node\PrintNode.',
        );
        $handler->render($node, self::$renderer, []);
    }

    #[TestDox('Компиляция выбрасывает исключение если получено не StatementNode')]
    public function testCompileException(): void
    {
        $handler = new RawHandler();
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessageIs(
            'Expected instance of StatementNode, got Vasoft\Joke\Templator\Parser\Node\PrintNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }
}
