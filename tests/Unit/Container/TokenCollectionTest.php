<?php

namespace Vasoft\Joke\Templator\Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Container\TokenCollection;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;

class TokenCollectionTest extends TestCase
{
    public function testAddWithDefault(): void
    {
        $collection = new TokenCollection();
        $collection->add(new TokenDescriptor('{++++', '++++}', PrintToken::class));
        self::assertCount(3, $collection->list());
        $expected = ['{++++', '{{', '{%'];
        sort($expected);
        $exists = array_keys($collection->list());
        sort($exists);
        self::assertSame($expected, $exists);
    }

    public function testAdd(): void
    {
        $collection = new TokenCollection();
        $collection->reset([]);
        $collection->add(new TokenDescriptor('{{', '}}', PrintToken::class));
        self::assertCount(1, $collection->list());
    }

    public function testAddTwice(): void
    {
        $collection = new TokenCollection();
        $collection->reset([]);
        $collection->add(new TokenDescriptor('{{', '}}', PrintToken::class));
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage('Token already exists');
        $collection->add(new TokenDescriptor('{{', '}}', StatementToken::class));
    }

    public function testUpsert(): void
    {
        $collection = new TokenCollection();
        $collection->reset([]);
        $descriptor = new TokenDescriptor('{{', '}}', StatementToken::class);
        $collection->add(new TokenDescriptor('{{', '}}', PrintToken::class));
        $collection->upsert($descriptor);
        $list = $collection->list();
        self::assertCount(1, $list);
        self::assertSame($descriptor, $list['{{']);
    }

    public function testAddSeveral(): void
    {
        $collection = new TokenCollection();
        $collection->reset([]);
        $collection->add(new TokenDescriptor('{{', '}}', PrintToken::class), 100);
        $collection->add(new TokenDescriptor('{%', '%}', StatementToken::class), 100);
        self::assertCount(2, $collection->list());
    }


    public function testReset(): void
    {
        $collection = new TokenCollection();
        $collection->reset([]);
        $collection->add(new TokenDescriptor('{9', '}}', PrintToken::class));
        $collection->add(new TokenDescriptor('{1', '}}', PrintToken::class));
        self::assertCount(2, $collection->list());

        $collection->reset([
            new TokenDescriptor('{12', '}}', PrintToken::class),
            new TokenDescriptor('{11', '}}', PrintToken::class),
            new TokenDescriptor('{15', '}}', PrintToken::class),
            new TokenDescriptor('{12', '12}', PrintToken::class),
        ]);
        /** @var array<string,TokenDescriptor> $list */
        $list = $collection->list();
        self::assertCount(3, $list);
        self::assertSame(['{12', '{11', '{15'], array_keys($list));
        self::assertSame('12}', $list['{12']->close);
    }

}
