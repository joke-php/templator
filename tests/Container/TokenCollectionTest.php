<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Container;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Container\TokenCollection;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Container\TokenCollection
 */
final class TokenCollectionTest extends TestCase
{
    public function testAddTwice(): void
    {
        $collection = new TokenCollection();
        $descriptor = new TokenDescriptor('<<', '>>', PrintToken::class);
        $collection->add($descriptor);
        $list = $collection->list();
        self::assertSame($descriptor, $list['<<']);
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage('Token with marker "<<" already exists.');
        $collection->add($descriptor);
    }

    public function testUpsert(): void
    {
        $collection = new TokenCollection();
        $collection->add(new TokenDescriptor('<<', '>>', PrintToken::class));
        $descriptor = new TokenDescriptor('<<', '>>', PrintToken::class);
        $collection->upsert($descriptor);
        $list = $collection->list();
        self::assertSame($descriptor, $list['<<']);
    }

    public function testReset(): void
    {
        $collection = new TokenCollection();
        $collection->add(new TokenDescriptor('<#', '#>', PrintToken::class));
        $descriptor1 = new TokenDescriptor('<<', '>>', PrintToken::class);
        $descriptor2 = new TokenDescriptor('<!', '!>', PrintToken::class);
        $collection->reset([$descriptor1, $descriptor2]);
        $list = $collection->list();
        self::assertCount(2, $list);
        self::assertSame($descriptor1, $list['<<']);
        self::assertSame($descriptor2, $list['<!']);
    }
}
