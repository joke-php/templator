<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Handler\Directive\EachHandler;
use Vasoft\Joke\Templator\Handler\Directive\IfHandler;
use Vasoft\Joke\Templator\Handler\Node\BlockNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
use Vasoft\Joke\Templator\Handler\Statement\CsrfHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\TemplatorConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\TemplatorConfig;
 */
final class TemplatorConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new TemplatorConfig();
        self::assertSame('UTF-8', $config->encoding);

        $tokens = $config->tokenCollection->list();
        self::assertCount(2, $tokens);
    }

    #[DataProvider('provideDefaultTokensCases')]
    public function testDefaultTokens(string $open, string $close): void
    {
        $config = new TemplatorConfig();
        $tokens = $config->tokenCollection->list();
        self::assertArrayHasKey($open, $tokens);
        self::assertSame($close, $tokens[$open]->close);
    }

    public static function provideDefaultTokensCases(): iterable
    {
        yield ['{{', '}}'];
        yield ['{%', '%}'];
    }

    #[DataProvider('provideDefaultNodeHandlerCases')]
    public function testDefaultNodeHandler(string $nodeClass, string $handlerClass): void
    {
        $config = new TemplatorConfig();
        self::assertSame($handlerClass, $config->getNodeHandler($nodeClass));
    }

    public static function provideDefaultNodeHandlerCases(): iterable
    {
        yield [TextNode::class, TextNodeHandler::class];
        yield [PrintNode::class, PrintNodeHandler::class];
        yield [BlockNode::class, BlockNodeHandler::class];
        yield [StatementNode::class, StatementNodeHandler::class];
    }

    #[DataProvider('provideDefaultDirectiveHandlerCases')]
    public function testDefaultDirectiveHandler(string $directive, string $handlerClass): void
    {
        $config = new TemplatorConfig();
        self::assertSame($handlerClass, $config->getDirectiveHandler($directive));
    }

    public static function provideDefaultDirectiveHandlerCases(): iterable
    {
        yield ['if', IfHandler::class];
        yield ['foreach', EachHandler::class];
        yield ['csrf', CsrfHandler::class];
    }

    public function testNodeHandlerNotRegistered(): void
    {
        $config = new TemplatorConfig();
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage("Handler for 'stdClass' not found.");
        $config->getNodeHandler(\stdClass::class);
    }

    public function testDirectiveHandlerNotRegistered(): void
    {
        $config = new TemplatorConfig();
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage("Handler for directive 'unknown' not found.");
        $config->getDirectiveHandler('unknown');
    }

    public function testCustomEncoding(): void
    {
        $config = new TemplatorConfig();
        $config->setEncoding('windows-1251');
        self::assertSame('windows-1251', $config->encoding);
        $config->freeze();
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Cannot modify frozen configuration of [Vasoft\Joke\Templator\TemplatorConfig].');
        $config->setEncoding('UTF-8');
    }
}
