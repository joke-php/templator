<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Support\FileSystem;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Cookies\CookieConfig;
use Vasoft\Joke\Http\Response\Html\PageBuilderConfig;
use Vasoft\Joke\Templator\Container\DeferService;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\TemplatedResponse;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\TemplatedResponse
 */
final class TemplatedResponseTest extends TestCase
{
    private string $tempDir;
    private ServiceContainer $container;
    private TemplateEngine $engine;
    private string $compiled;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/joke_tpl_response_test_' . uniqid();
        mkdir($this->tempDir);
        $this->compiled = '<?php echo "Hi";';

        $this->container = new ServiceContainer();
        $this->container->registerSingleton(TemplatorConfig::class, TemplatorConfig::class);
        $this->container->registerSingleton(DeferService::class, DeferService::class);
        $fs = new FileSystem($this->tempDir);
        $this->container->registerSingleton(FileSystem::class, $fs);
        $this->container->registerAlias('paths', FileSystem::class);
        $this->container->registerSingleton(PageBuilderConfig::class, PageBuilderConfig::class);
        $this->container->registerSingleton(CookieConfig::class, CookieConfig::class);
        $lexer = self::getStubBuilder(LexerInterface::class)
            ->disableOriginalConstructor()
            ->getStub();
        $lexer->method('tokenize')->willReturn([]);
        $this->container->registerSingleton(LexerInterface::class, $lexer);
        $parser = self::getStubBuilder(ParserInterface::class)
            ->disableOriginalConstructor()
            ->getStub();
        $parser->method('parse')->willReturn([]);
        $this->container->registerSingleton(ParserInterface::class, $parser);

        $compiler = self::getStubBuilder(NodeProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getStub();

        $compiler->method('process')->willReturnCallback(fn(array $ast, array $context) => $this->compiled);
        $this->container->registerSingleton('templator.compiler', $compiler);


        $env = self::getStubBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getStub();
        $env->method('getBasePath')->willReturn($this->tempDir);
        $this->container->registerSingleton('env', $env);


        $this->engine = new TemplateEngine($this->container);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }

    #[DataProvider('provideTemplatedResponseCases')]
    public function testTemplatedResponse(string $templateName, string $templateNameAfter): void
    {
        $template = '%%testIncludeFile%%';
        $context = ['testIncludeFile' => 'testIncludeFileValue'];
        $fileName = $this->tempDir . '/testIncludeFile.php';
        file_put_contents($fileName, $template);

        $response = new TemplatedResponse($this->container, $this->engine, $templateName);
        $response->builder->setTitle('example <small>1</small>');
        $containerId = spl_object_id($this->container);
        $engineId = spl_object_id($this->engine);

        if ('' !== $templateNameAfter) {
            $response->setTemplateName($templateNameAfter);
            $templateName = $templateNameAfter;
        }
        if ('' === $templateName) {
            $templateName = 'default';
        }

        $this->compiled = <<<'PHP'
            <?php
                echo "Hi",PHP_EOL;
                echo $context["testIncludeFile"],PHP_EOL;
                echo spl_object_id($container), PHP_EOL;
                echo spl_object_id($engine), PHP_EOL;
                echo $response->getDeferService()->registerRaw('page.title',(string)($context['page']['title']??'')).PHP_EOL;
                echo $response->getDeferService()->register('page.title',(string)($context['page']['title']??'')).PHP_EOL;
                echo $engine->templateName, PHP_EOL;
            PHP;

        $expected = <<<TEXT
            <html lang="ru">
            <head>
            <title>example 1</title>
            <meta charset="UTF-8">
            </head>
            <body>
            Hi
            {$context['testIncludeFile']}
            {$containerId}
            {$engineId}
            example <small>1</small>
            example &lt;small&gt;1&lt;/small&gt;
            {$templateName}

            </body>
            </html>
            TEXT;

        $content = $response->show($fileName, $context)->getBodyAsString();

        self::assertSame($expected, $content);
    }

    public static function provideTemplatedResponseCases(): iterable
    {
        yield ['', ''];
        yield ['main', ''];
        yield ['main', 'custom'];
    }
}
