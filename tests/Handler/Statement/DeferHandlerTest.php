<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Statement;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Stub;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Support\FileSystem;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Handler\Statement\DeferHandler;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Statement\DeferHandler
 */
#[TestDox('DeferHandler - логика директивы отложенного вывода значений')]
#[CoversClass(DeferHandler::class)]
final class DeferHandlerTest extends TestCase
{
    /**
     * @var (object&Stub)|Stub
     */
    private static DefaultRenderer|Stub $renderer;
    private static TemplateEngine $engine;
    private static string $tempDir = '';

    public static function setUpBeforeClass(): void
    {
        self::$tempDir = sys_get_temp_dir() . '/joke_tpl_response_test_' . uniqid();
        mkdir(self::$tempDir);
        $container = new ServiceContainer();
        $fs = new FileSystem(self::$tempDir);
        $container->registerSingleton(FileSystem::class, $fs);
        $container->registerAlias('paths', FileSystem::class);

        self::$engine = new TemplateEngine($container);
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    public static function tearDownAfterClass(): void
    {
        if (!is_dir(self::$tempDir)) {
            return;
        }
        rmdir(self::$tempDir);
    }

    #[TestDox('Генерирует код для экранированных значений')]
    public function testDefer(): void
    {
        $handler = new DeferHandler(self::$engine);
        $node = new StatementNode(StatementNode::class, 'defer', 'myVar');
        $context = ['myVar' => 1];
        self::assertSame(
            "<?= \$response->getDeferService()->register('myVar',(string)(\$context['myVar']??'')); ?>",
            $handler->compile($node, self::$renderer, $context),
        );
    }

    #[TestDox('Генерирует код для не экранированных значений, понимает пути переменных')]
    public function testDeferRaw(): void
    {
        $handler = new DeferHandler(self::$engine);
        $node = new StatementNode(StatementNode::class, 'defer', 'page.myVar raw');
        $context = [];
        self::assertSame(
            "<?= \$response->getDeferService()->registerRaw('page.myVar',(string)(\$context['page']['myVar']??'')); ?>",
            $handler->compile($node, self::$renderer, $context),
        );
    }

    #[TestDox('Выбрасывает исключение если нет параметров')]
    public function testNoVarException(): void
    {
        $handler = new DeferHandler(self::$engine);
        $node = new StatementNode(StatementNode::class, 'defer', '');
        self::expectException(RequiredParameterException::class);
        self::expectExceptionMessageIs('Required parameter "varName" for directive "defer" is missing.');
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Компиляция выбрасывает исключение если получено не StatementNode')]
    public function testException(): void
    {
        $handler = new DeferHandler(self::$engine);
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessageIs(
            'Expected instance of StatementNode, got Vasoft\Joke\Templator\Parser\Node\PrintNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Рендер выбрасывает исключение о нереализованном функционале')]
    public function testRenderExceptionNotImplement(): void
    {
        $handler = new DeferHandler(self::$engine);
        $node = new StatementNode(StatementNode::class, 'defer', 'page.myVar raw');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs('Not implemented yet.');
        $handler->render($node, self::$renderer, []);
    }
}
