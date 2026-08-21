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
use Vasoft\Joke\Templator\Handler\Statement\IncludeHandler;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Statement\IncludeHandler
 */
#[TestDox('IncludeHandler - логика директивы пдключения файлов')]
#[CoversClass(IncludeHandler::class)]
final class IncludeHandlerTest extends TestCase
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

    #[TestDox('Подключение css по умолчанию')]
    public function testIncludeCss(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'css fileVar');
        self::assertSame(
            "<?php \$response->builder->css->addToBody(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
    }

    #[TestDox('Подключение css c указанием положения')]
    public function testIncludeCssPosition(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'css fileVar body');
        self::assertSame(
            "<?php \$response->builder->css->addToBody(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
        $node = new StatementNode(StatementNode::class, 'include', 'css fileVar head');
        self::assertSame(
            "<?php \$response->builder->css->addToHead(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
    }

    #[TestDox('Если передано недопустимое положение CSS - размещение по умолчанию')]
    public function testIncludeCssUnknownPosition(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'css fileVar unknown');
        self::assertSame(
            "<?php \$response->builder->css->addToBody(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
    }

    #[TestDox('Подключение js по умолчанию')]
    public function testIncludeJs(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'js fileVar');
        self::assertSame(
            "<?php \$response->builder->js->addToBody(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
    }

    #[TestDox('Подключение js c указанием положения')]
    public function testIncludeJsPosition(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'js fileVar body');
        self::assertSame(
            "<?php \$response->builder->js->addToBody(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
        $node = new StatementNode(StatementNode::class, 'include', 'js fileVar head');
        self::assertSame(
            "<?php \$response->builder->js->addToHead(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
    }

    #[TestDox('Если передано недопустимое положение JS - размещение по умолчанию')]
    public function testIncludeJsUnknownPosition(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'js fileVar unknown');
        self::assertSame(
            "<?php \$response->builder->js->addToBody(\$context['fileVar']); ?>",
            $handler->compile($node, self::$renderer, []),
        );
    }

    #[TestDox('Исключение если не передано имя файла')]
    public function testExceptionFileName(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'js');
        self::expectException(RequiredParameterException::class);
        self::expectExceptionMessageIs('Required parameter "filename" for directive "include" is missing.');
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Исключение если не передан тип')]
    public function testExceptionType(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', '');
        self::expectException(RequiredParameterException::class);
        self::expectExceptionMessageIs('Required parameter "type" for directive "include" is missing.');
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Исключение если не передан неизвестный тип')]
    public function testExceptionUnknownType(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'filename');
        self::expectException(RequiredParameterException::class);
        self::expectExceptionMessageIs('Required parameter "type" for directive "include" is missing.');
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Рендер выбрасывает исключение о нереализованном функционале')]
    public function testRenderExceptionNotImplement(): void
    {
        $handler = new IncludeHandler();
        $node = new StatementNode(StatementNode::class, 'include', 'filename');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs('Not implemented yet.');
        $handler->render($node, self::$renderer, []);
    }

    #[TestDox('Компиляция выбрасывает исключение если получено не StatementNode')]
    public function testException(): void
    {
        $handler = new IncludeHandler();
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessageIs(
            'Expected instance of StatementNode, got Vasoft\Joke\Templator\Parser\Node\PrintNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }
}
