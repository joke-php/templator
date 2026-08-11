<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Directive;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Stub;
use Vasoft\Joke\Support\FileSystem;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\Directive\LayoutHandler;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Directive\LayoutHandler
 */
#[CoversClass(LayoutHandler::class)]
#[TestDox('LayoutHandler - реализация каркаса страницы')]
final class LayoutHandlerTest extends TestCase
{
    private static Stub|DefaultRenderer $renderer;
    private static Stub|TemplateEngine $engine;
    private static Stub|FileSystem $fs;

    public static function setUpBeforeClass(): void
    {
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();

        self::$engine = self::getStubBuilder(TemplateEngine::class)
            ->disableOriginalConstructor()
            ->getStub();

        $reflection = new \ReflectionProperty(TemplateEngine::class, 'layoutsPath');
        $reflection->setValue(self::$engine, '/layouts/');

        self::$fs = self::getStubBuilder(FileSystem::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    #[TestDox('Компилирует код помещающий дочерние узлы в заданный каркас')]
    public function testCompile(): void
    {
        $handler = new LayoutHandler(self::$engine, self::$fs);
        $node = new BlockNode(StatementToken::class, 'test', 'main');
        self::$fs->method('at')->willReturn('/layouts/main.php');
        self::$renderer->method('process')->willReturn('<?=$content[\'test\']?>');
        $expected = <<<'PHP'
                <?php
                ob_start();
                ?>
                <?=$content['test']?>
                <?php
                 $__content = ob_get_clean();
                $__layoutContext = ['__layout' => ['content' => $__content]];
                $engine->includeFile('/layouts/main.php',$__layoutContext,0);
                ?>
            PHP;
        self::assertSame($expected, $handler->compile($node, self::$renderer, []));
    }

    #[TestDox('Рендер выбрасывает исключение если получено не StatementNode')]
    public function testRenderException(): void
    {
        $handler = new LayoutHandler(self::$engine, self::$fs);
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs('Not implemented yet.');
        $handler->render($node, self::$renderer, []);
    }

    #[TestDox('Рендер выбрасывает исключение о нереализованном функционале')]
    public function testRenderExceptionNotImplement(): void
    {
        $handler = new LayoutHandler(self::$engine, self::$fs);
        $node = new BlockNode(StatementToken::class, 'test', '');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs('Not implemented yet.');
        $handler->render($node, self::$renderer, []);
    }

    #[TestDox('Компиляция выбрасывает исключение если получено не BlockNode')]
    public function testCompileException(): void
    {
        $handler = new LayoutHandler(self::$engine, self::$fs);
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessageIs(
            'Expected instance of BlockNode, got Vasoft\Joke\Templator\Parser\Node\PrintNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }
}
