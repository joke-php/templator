<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Lexer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\StatementToken;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Lexer\StatementToken;
 */
final class StatementTokenTest extends TestCase
{
    #[DataProvider('provideGetDirectiveCases')]
    public function testGetDirective(string $raw, string $expected): void
    {
        $token = new StatementToken($raw);
        self::assertSame($expected, $token->getDirective());
    }

    public static function provideGetDirectiveCases(): iterable
    {
        yield ['else', 'else'];
        yield ['component type="example" props="test"', 'component'];
    }

    #[DataProvider('provideGetArgumentsCases')]
    public function testGetArguments(string $raw, string $expected): void
    {
        $token = new StatementToken($raw);
        self::assertSame($expected, $token->getArguments());
    }

    public static function provideGetArgumentsCases(): iterable
    {
        yield ['else', ''];
        yield ['component type="example" props="test"', 'type="example" props="test"'];
    }
}
