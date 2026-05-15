<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Container;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Container\DirectiveCollection;
use Vasoft\Joke\Templator\Container\DirectiveType;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Container\DirectiveCollection;
 */
final class DirectiveCollectionTest extends TestCase
{
    public function testAddTwice(): void
    {
        $collection = new DirectiveCollection();
        $collection->add(StatementToken::class, 'css');
        $collection->add(TextToken::class, 'css');
        self::assertSame(DirectiveType::SINGLE, $collection->getType(StatementToken::class, 'css'));
        self::assertSame(DirectiveType::SINGLE, $collection->getType(TextToken::class, 'css'));
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage(
            "Directive 'css' already defined for type 'Vasoft\\Joke\\Templator\\Lexer\\TextToken'.",
        );
        $collection->add(TextToken::class, 'css');
    }

    public function testUpsert(): void
    {
        $collection = new DirectiveCollection();
        $collection->add(StatementToken::class, 'css', '/css', ['csselse', 'cssnull']);
        self::assertSame('css', $collection->getOpenDirective(StatementToken::class, '/css'));
        $collection->upsert(StatementToken::class, 'css', 'endcss', ['empty']);
        self::assertSame('css', $collection->getOpenDirective(StatementToken::class, 'endcss'));
        self::assertSame('', $collection->getOpenDirective(StatementToken::class, '/css'));
        self::assertSame('', $collection->getOpenDirective(StatementToken::class, 'cssnull'));
        self::assertSame('', $collection->getOpenDirective(StatementToken::class, 'csselse'));
        self::assertSame('css', $collection->getOpenDirective(StatementToken::class, 'empty'));
    }

    public function testGetOpenDirective(): void
    {
        $collection = new DirectiveCollection();
        $collection->add(StatementToken::class, 'css', '/css', ['csselse', 'cssnull']);
        self::assertSame('css', $collection->getOpenDirective(StatementToken::class, '/css'));
        self::assertSame('css', $collection->getOpenDirective(StatementToken::class, 'cssnull'));
        self::assertSame('css', $collection->getOpenDirective(StatementToken::class, 'csselse'));
        self::assertSame('', $collection->getOpenDirective(StatementToken::class, 'unknown'));
    }

    public function testGetType(): void
    {
        $collection = new DirectiveCollection();
        $collection->add(StatementToken::class, 'css');
        $collection->add(TextToken::class, 'css', '/css', ['empty']);
        self::assertSame(DirectiveType::SINGLE, $collection->getType(StatementToken::class, 'css'));
        self::assertSame(DirectiveType::BEGIN, $collection->getType(TextToken::class, 'css'));
        self::assertSame(DirectiveType::END, $collection->getType(TextToken::class, '/css'));
        self::assertSame(DirectiveType::BRANCH, $collection->getType(TextToken::class, 'empty'));
        self::assertSame(DirectiveType::UNKNOWN, $collection->getType(TextToken::class, 'unknown'));
    }
}
