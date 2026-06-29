<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\Stub;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Exceptions\LexerException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\TemplateEngine;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\TemplateEngine;
 */
final class TemplateEngineTest extends TestCase
{
    use PHPMock;

    private ServiceContainer $container;
    private static string $cachePath = '';
    private string $lastTemplate = '';
    private array $lastContext = [];
    private string $compiled = '';

    public function testCompileFile(): void
    {
        $template = '%%testCompileFile%%';
        $context = ['testCompileFile' => 1];
        $fileName = self::$cachePath . '/testCompileFile.php';
        file_put_contents($fileName, $template);
        $engine = new TemplateEngine($this->container);
        $result = $engine->compileFile($fileName, $context);

        self::assertSame($this->compiled, $result);
        self::assertSame($template, $this->lastTemplate);
        self::assertSame($context, $this->lastContext);
    }

    public function testCompileFileNotFoundException(): void
    {
        $context = ['testCompileFile' => 1];
        $fileName = self::$cachePath . '/testCompileFileNotFoundException.php';
        $engine = new TemplateEngine($this->container);
        self::expectException(TemplatorException::class);
        self::expectExceptionMessageIs("Template file not found: {$fileName}.");

        $engine->compileFile($fileName, $context);
    }

    #[RunInSeparateProcess]
    public function testCompileFileUnableToRead(): void
    {
        $fileGetContents = $this->getFunctionMock('Vasoft\Joke\Templator', 'file_get_contents');
        $fileGetContents->expects(self::once())->willReturn(false);

        $template = '%%testCompileFileUnableToRead%%';
        $context = ['testCompileFileUnableToRead' => 1];
        $fileName = self::$cachePath . '/testCompileFileUnableToRead.php';
        file_put_contents($fileName, $template);
        $engine = new TemplateEngine($this->container);
        self::expectException(TemplatorException::class);
        self::expectExceptionMessageIs("Unable to read template file: {$fileName}.");

        $engine->compileFile($fileName, $context);
    }

    public function testCompileString(): void
    {
        $template = '%%test1%%';
        $context = ['test1' => 1];
        $engine = new TemplateEngine($this->container);
        $result = $engine->compileString($template, $context);

        self::assertSame($this->compiled, $result);
        self::assertSame($template, $this->lastTemplate);
        self::assertSame($context, $this->lastContext);
    }

    public function testTemplateException(): void
    {
        /** @var Stub $lexer */
        $lexer = $this->container->get(LexerInterface::class);
        $lexer->method('tokenize')->willThrowException(new LexerException('Lexer Exception'));

        $template = '%%test3%%';
        $context = ['test3' => 3];
        $engine = new TemplateEngine($this->container);
        self::expectException(LexerException::class);
        self::expectExceptionMessageIs('Lexer Exception');
        $engine->compileString($template, $context);
    }

    public function testOtherException(): void
    {
        /** @var Stub $lexer */
        $lexer = $this->container->get(LexerInterface::class);
        $lexer->method('tokenize')->willThrowException(new \Exception('Other Exception'));

        $template = '%%test2%%';
        $context = ['test2' => 2];
        $engine = new TemplateEngine($this->container);

        try {
            $engine->compileString($template, $context);
            self::fail('Expected TemplatorException was not thrown.');
        } catch (TemplatorException $e) {
            self::assertSame('Error compile template: Other Exception', $e->getMessage());
            $previous = $e->getPrevious();
            self::assertInstanceOf(\Exception::class, $previous);
            self::assertSame('Other Exception', $previous->getMessage());
        }
    }

    public function testIncludeFile(): void
    {
        $template = '%%testIncludeFile%%';
        $context = ['testIncludeFile' => 'testIncludeFileValue'];
        $fileName = self::$cachePath . '/testIncludeFile.php';
        file_put_contents($fileName, $template);

        $this->compiled = <<<'PHP'
            <?php
                echo "Hi",PHP_EOL;
                echo $context["testIncludeFile"],PHP_EOL;
                echo spl_object_id($container), PHP_EOL;
                echo spl_object_id($templateEngine), PHP_EOL;
            PHP;
        $engine = new TemplateEngine($this->container);
        $containerId = spl_object_id($this->container);
        $engineId = spl_object_id($engine);

        $expected = <<<TEXT
            Hi
            {$context['testIncludeFile']}
            {$containerId}
            {$engineId}

            TEXT;

        ob_start();
        $engine->includeFile($fileName, $context);
        $content = ob_get_clean();

        self::assertSame($expected, $content);
    }

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
        $this->compiled = '<?php echo "Hi";';

        $env = self::getStubBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getStub();
        $env->method('getBasePath')->willReturn(self::$cachePath);
        $this->container->registerSingleton('env', $env);

        $lexer = self::getStubBuilder(LexerInterface::class)
            ->disableOriginalConstructor()
            ->getStub();
        $lexer->method('tokenize')->willReturnCallback(function (string $template) {
            $this->lastTemplate = $template;

            return [];
        });
        $this->container->registerSingleton(LexerInterface::class, $lexer);

        $parser = self::getStubBuilder(ParserInterface::class)
            ->disableOriginalConstructor()
            ->getStub();
        $parser->method('parse')->willReturn([]);
        $this->container->registerSingleton(ParserInterface::class, $parser);

        $compiler = self::getStubBuilder(NodeProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getStub();
        $compiler->method('process')->willReturnCallback(function (array $ast, array $context) {
            $this->lastContext = $context;

            return $this->compiled;
        });
        $this->container->registerSingleton('templator.compiler', $compiler);
    }

    public static function setUpBeforeClass(): void
    {
        self::ensureTemporaryDir();
    }

    public static function tearDownAfterClass(): void
    {
        self::clean();
    }

    private static function ensureTemporaryDir(): void
    {
        self::$cachePath = sys_get_temp_dir() . '/joke-template-engine-' . uniqid() . '/';
        mkdir(self::$cachePath, 0o755, true);
    }

    private static function clean(): void
    {
        if (!file_exists(self::$cachePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$cachePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir(self::$cachePath);
    }
}
