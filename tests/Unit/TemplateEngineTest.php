<?php

namespace Vasoft\Joke\Templator\Tests\Unit;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Ast\DefaultParser;
use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Contracts\Ast\ParserInterface;
use Vasoft\Joke\Templator\Contracts\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Ast\TagHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\Render\Handlers\EchoHandler;
use Vasoft\Joke\Templator\TemplateEngine;

class TemplateEngineTest extends TestCase
{
    use PHPMock;

    private TemplateEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new TemplateEngine(new ServiceContainer());
        $this->engine->registerTag('echo', new EchoHandler());
    }

    public function testRenderStringWithEcho(): void
    {
        $template = 'Hello ({{name}}) {%echo name%}!';
        $context = ['name' => '<b>Alice</b>'];

        $result = $this->engine->renderString($template, $context);
        self::assertSame("Hello (<b>Alice</b>) <?php echo \$context['name'];?>!", $result);
    }

    public function testRenderStringWithEscaped(): void
    {
        $template = 'Hello <j-echo escaped value="content" j-static/> <j-echo escaped value="content"/>!';
        $context = ['content' => '<b>Bold</b>'];

        $result = $this->engine->renderString($template, $context);
        self::assertSame(
            'Hello &lt;b&gt;Bold&lt;/b&gt; <?php echo htmlspecialchars((string)$context[\'content\'], ENT_QUOTES, \'UTF-8\');?>!',
            $result
        );
    }

    public function testRenderFile(): void
    {
        $templateFile = dirname(__DIR__) . '/Fixtures/test.joke';
        file_put_contents($templateFile, 'Name: <j-echo value="name"/>');

        try {
            $result = $this->engine->renderFile($templateFile, ['name' => 'Bob']);
            self::assertSame('Name: <?php echo $context[\'name\'];?>', $result);
        } finally {
            unlink($templateFile);
        }
    }

    public function testThrowsExceptionOnUnknownTag(): void
    {
        self::expectException(TemplatorException::class);
        $this->engine->renderString('<j-unknown/>', []);
    }

    public function testThrowsExceptionOnMissingFile(): void
    {
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage('Template file not found: /non/existent/file.joke');
        $this->engine->renderFile('/non/existent/file.joke', []);
    }

    public function testErrorRenderingTemplate(): void
    {
        $this->engine = new TemplateEngine(new ServiceContainer());
        $this->engine->registerTag(
            'error',
            new class implements TagHandlerInterface {

                public function handle(TagNode $node, array $context, RendererInterface $renderer): string
                {
                    throw new \Error('Some error');
                }
            }
        );

        self::expectException(TemplatorException::class);
        self::expectExceptionMessage('Error rendering template: Some error');
        $this->engine->renderString('<j-error j-static />', []);
    }

    #[RunInSeparateProcess]
    public function testErrorReadingTemplate(): void
    {
        $fileGetContentMock = $this->getFunctionMock('Vasoft\Joke\Templator\Core', 'file_get_contents');
        $fileExistsMock = $this->getFunctionMock('Vasoft\Joke\Templator\Core', 'file_exists');
        $fileExistsMock->expects(self::once())->willReturn(true);
        $fileGetContentMock->expects(self::once())->willReturn(false);
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage('Unable to read template file: /existent/file.joke');
        $this->engine->renderFile('/existent/file.joke', []);
    }

    public function testUsesDefaultImplementationsWhenNotInContainer(): void
    {
        $container = new ServiceContainer();
        $engine = new TemplateEngine($container);

        $reflection = new \ReflectionClass($engine);

        $lexerProp = $reflection->getProperty('lexer');
        $lexerProp->setAccessible(true);
        $lexer = $lexerProp->getValue($engine);
        self::assertInstanceOf(DefaultLexer::class, $lexer);

        $parserProp = $reflection->getProperty('parser');
        $parserProp->setAccessible(true);
        $parser = $parserProp->getValue($engine);
        self::assertInstanceOf(DefaultParser::class, $parser);

        $rendererProp = $reflection->getProperty('renderer');
        $rendererProp->setAccessible(true);
        $renderer = $rendererProp->getValue($engine);
        self::assertInstanceOf(DefaultRenderer::class, $renderer);

        $compilerProp = $reflection->getProperty('compiler');
        $compilerProp->setAccessible(true);
        $compiler = $compilerProp->getValue($engine);
        self::assertInstanceOf(DefaultCompiler::class, $compiler);
    }

    public function testUsesContainerImplementationsWhenRegistered(): void
    {
        $container = new ServiceContainer();

        $stubLexer = new class implements LexerInterface {
            public function tokenize(string $template): array { return []; }
        };

        $stubParser = new class implements ParserInterface {
            public function parse(array $tokens): array { return []; }
        };

        $stubCompiler = new class implements CompilerInterface {

            public function compile(array $ast): string
            {
                return '';
            }

            public function registerTagCompiler(string $tagName, string $compilerClass): static
            {
                return $this;
            }

            public function registerNodeCompiler(string $nodeClass, string $compilerClass): static
            {
                return $this;
            }
        };

        $stubRenderer = new class implements RendererInterface {
            public function registerTag(string $tagName, TagHandlerInterface $handler): static
            {
                return $this;
            }


            public function optimizeStaticNodes(array $nodes, array $context): array
            {
                return [];
            }
        };

        $container->registerSingleton(LexerInterface::class, $stubLexer);
        $container->register(ParserInterface::class, $stubParser);
        $container->registerSingleton(RendererInterface::class, $stubRenderer);
        $container->registerSingleton(CompilerInterface::class, $stubCompiler);

        $engine = new TemplateEngine($container);

        $reflection = new \ReflectionClass($engine);


        $lexerProp = $reflection->getProperty('lexer');
        $lexerProp->setAccessible(true);
        self::assertSame($stubLexer, $lexerProp->getValue($engine));

        $parserProp = $reflection->getProperty('parser');
        $parserProp->setAccessible(true);
        self::assertSame($stubParser, $parserProp->getValue($engine));

        $rendererProp = $reflection->getProperty('renderer');
        $rendererProp->setAccessible(true);
        self::assertSame($stubRenderer, $rendererProp->getValue($engine));

        $compilerProp = $reflection->getProperty('compiler');
        $compilerProp->setAccessible(true);
        self::assertSame($stubCompiler, $compilerProp->getValue($engine));
    }
}
