<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Config\EnvironmentLoader;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Handler\Directive\EachHandler;
use Vasoft\Joke\Templator\Handler\Directive\IfHandler;
use Vasoft\Joke\Templator\Handler\Node\BlockNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
use Vasoft\Joke\Templator\Handler\Statement\CsrfHandler;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatorConfig;
use Vasoft\Joke\Templator\TemplatorProvider;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\TemplatorProvider
 */
final class TemplatorProviderTest extends TestCase
{
    /**
     * @var (object&Stub)|Stub
     */
    private static Stub|ServiceContainer $container;

    public static function setUpBeforeClass(): void
    {
        self::$container = self::getStubBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getStub();
    }

    public function testProvideConfigs(): void
    {
        $provider = new TemplatorProvider(self::$container);
        self::assertSame([TemplatorConfig::class], $provider->provideConfigs());
    }

    #[DataProvider('provideBootDefaultTokensCases')]
    public function testBootDefaultTokens(string $open, string $close): void
    {
        $container = new ServiceContainer();
        $config = new TemplatorConfig();
        $container->registerSingleton(TemplatorConfig::class, $config);
        $provider = new TemplatorProvider($container);
        $provider->boot();
        $tokens = $config->tokenCollection->list();
        self::assertArrayHasKey($open, $tokens);
        self::assertSame($close, $tokens[$open]->close);
    }

    public static function provideBootDefaultTokensCases(): iterable
    {
        yield ['{{', '}}'];
        yield ['{%', '%}'];
    }

    #[DataProvider('provideBootDefaultNodeHandlerCases')]
    public function testBootDefaultNodeHandler(string $nodeClass, string $handlerClass): void
    {
        $container = new ServiceContainer();
        $config = new TemplatorConfig();
        $container->registerSingleton(TemplatorConfig::class, $config);
        $provider = new TemplatorProvider($container);
        $provider->boot();
        self::assertSame($handlerClass, $config->getNodeHandler($nodeClass));
    }

    public static function provideBootDefaultNodeHandlerCases(): iterable
    {
        yield [TextNode::class, TextNodeHandler::class];
        yield [PrintNode::class, PrintNodeHandler::class];
        yield [BlockNode::class, BlockNodeHandler::class];
        yield [StatementNode::class, StatementNodeHandler::class];
    }

    #[DataProvider('provideBootDefaultDirectiveHandlerCases')]
    public function testBootDefaultDirectiveHandler(string $directive, string $handlerClass): void
    {
        $container = new ServiceContainer();
        $config = new TemplatorConfig();
        $container->registerSingleton(TemplatorConfig::class, $config);
        $provider = new TemplatorProvider($container);
        $provider->boot();
        self::assertSame($handlerClass, $config->getDirectiveHandler($directive));
    }

    public static function provideBootDefaultDirectiveHandlerCases(): iterable
    {
        yield ['if', IfHandler::class];
        yield ['foreach', EachHandler::class];
        yield ['csrf', CsrfHandler::class];
    }

    public function testProvides(): void
    {
        $provider = new TemplatorProvider(self::$container);
        self::assertSame([TemplateEngine::class], $provider->provides());
    }

    public function testRegister(): void
    {
        $container = new ServiceContainer();
        $container->registerSingleton('env', new Environment(new EnvironmentLoader('/var')));
        $container->registerSingleton(TemplatorConfig::class, new TemplatorConfig());
        $provider = new TemplatorProvider($container);
        $provider->register();
        self::assertTrue($container->has(TemplateEngine::class));
        self::assertTrue($container->has(LexerInterface::class));
        self::assertTrue($container->has('templator.compiler'));
        self::assertTrue($container->has('templator.renderer'));
        self::assertInstanceOf(TemplateEngine::class, $container->get(TemplateEngine::class));
        self::assertInstanceOf(DefaultLexer::class, $container->get(LexerInterface::class));
        self::assertInstanceOf(DefaultCompiler::class, $container->get('templator.compiler'));
        self::assertInstanceOf(DefaultRenderer::class, $container->get('templator.renderer'));
    }

    public function testBuildConfig(): void
    {
        $provider = new TemplatorProvider(self::$container);
        $config = $provider->buildConfig(TemplatorConfig::class, self::$container);
        self::assertInstanceOf(TemplatorConfig::class, $config);
    }

    public function testBuildConfigUnknown(): void
    {
        $provider = new TemplatorProvider(self::$container);
        self::expectException(UnknownConfigException::class);
        self::expectExceptionMessageIs('Unknown config class: Vasoft\Joke\Config\Exceptions\ConfigException');
        $provider->buildConfig(ConfigException::class, self::$container);
    }
}
