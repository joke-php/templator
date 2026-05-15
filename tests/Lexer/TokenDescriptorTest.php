<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Lexer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Lexer\TokenDescriptor;
 */
final class TokenDescriptorTest extends TestCase
{
    #[DataProvider('provideCalculatedCases')]
    public function testCalculated(string $open, string $close, int $expectedOpen, int $expectedClose): void
    {
        $descriptor = new TokenDescriptor($open, $close, PrintToken::class);
        self::assertSame($expectedOpen, $descriptor->openLength);
        self::assertSame($expectedClose, $descriptor->closeLength);
    }

    public static function provideCalculatedCases(): iterable
    {
        yield ['<', '>', 1, 1];
        yield ['{{', '%}}}', 2, 4];
    }
}
