<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Stub;
use Vasoft\Joke\Templator\Component\ComponentHandler;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Component\ComponentHandler
 */
#[CoversClass(ComponentHandler::class)]
#[TestDox('ComponentHandler - обработчик подключения компонента')]
final class ComponentHandlerTest extends TestCase
{
    private static Stub|DefaultRenderer $renderer;
    private static Stub|TemplateEngine $engine;

    public static function setUpBeforeClass(): void
    {
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    #[TestDox('Компилирует код подключающий компонент')]
    public function testCompile(): void
    {
        $handler = new ComponentHandler();
        $node = new BlockNode(StatementToken::class, 'component', 'vasoft.test');
        $expected = <<<'PHP'
                <?php $component = $components->get('vasoft.test');
                $component->setOptions(null)->compile($engine, $response);
                ?>
            PHP;
        self::assertSame($expected, $handler->compile($node, self::$renderer, []));
    }

    #[TestDox('Компиляция выбрасывает исключение если получено не BlockNode')]
    public function testCompileException(): void
    {
        $handler = new ComponentHandler();
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(CompileException::class);
        self::expectExceptionMessageIs(
            'Expected instance of StatementNode, got Vasoft\Joke\Templator\Parser\Node\PrintNode.',
        );
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Компонент с заданным шаблоном и лишние пробелы между параметрами игнорируются')]
    public function testCustomTemplate(): void
    {
        $handler = new ComponentHandler();
        $node = new BlockNode(StatementToken::class, 'component', 'vasoft.test  custom');
        $expected = <<<'PHP'
                <?php $component = $components->get('vasoft.test');$component->setTemplateName('custom');
                $component->setOptions(null)->compile($engine, $response);
                ?>
            PHP;
        self::assertSame($expected, $handler->compile($node, self::$renderer, []));
    }

    #[TestDox('Параметры передаются в компонент')]
    public function testOptions(): void
    {
        $handler = new ComponentHandler();
        $node = new BlockNode(StatementToken::class, 'component', 'vasoft.test  custom   props');
        $expected = <<<'PHP'
                <?php $component = $components->get('vasoft.test');$component->setTemplateName('custom');
                $component->setOptions($context['props']??null)->compile($engine, $response);
                ?>
            PHP;
        self::assertSame($expected, $handler->compile($node, self::$renderer, []));
    }

    #[TestDox('Выбрасывает исключение если не передано имя компонента')]
    public function testExceptionIfEmptyLayoutName(): void
    {
        $handler = new ComponentHandler();
        $node = new BlockNode(StatementToken::class, 'component', '');
        self::expectException(RequiredParameterException::class);
        self::expectExceptionMessageIs('Required parameter "componentName" for directive "component" is missing.');
        $handler->compile($node, self::$renderer, []);
    }

    #[TestDox('Рендер выбрасывает исключение если получено не StatementNode')]
    public function testRenderException(): void
    {
        $handler = new ComponentHandler();
        $node = new PrintNode(StatementToken::class, 'test');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs('Not implemented yet.');
        $handler->render($node, self::$renderer, []);
    }

    #[TestDox('Рендер выбрасывает исключение о нереализованном функционале')]
    public function testRenderExceptionNotImplement(): void
    {
        $handler = new ComponentHandler();
        $node = new BlockNode(StatementToken::class, 'test', '');
        self::expectException(RenderingException::class);
        self::expectExceptionMessageIs('Not implemented yet.');
        $handler->render($node, self::$renderer, []);
    }
}
