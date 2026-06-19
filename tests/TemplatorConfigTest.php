<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Templator\Handler\Node\BlockNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
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
}
