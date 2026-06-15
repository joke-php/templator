<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Lexer;

use Vasoft\Joke\Templator\Exceptions\LexerException;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Lexer\DefaultLexer
 */
final class DefaultLexerTest extends TestCase
{
    public function testLexer(): void
    {
        $template = <<<'HTML'
            {{testVariable}}
            Single text
            {% each items as item %}
                {{item}}
            {%endeach%}
            HTML;
        $lexer = new DefaultLexer(new TemplatorConfig());
        $list = $lexer->tokenize($template);
        self::assertCount(7, $list);
        self::assertInstanceOf(PrintToken::class, $list[0]);
        self::assertInstanceOf(TextToken::class, $list[1]);
        self::assertInstanceOf(StatementToken::class, $list[2]);
        self::assertInstanceOf(TextToken::class, $list[3]);
        self::assertInstanceOf(PrintToken::class, $list[4]);
        self::assertInstanceOf(TextToken::class, $list[5]);
        self::assertInstanceOf(StatementToken::class, $list[6]);
        self::assertSame('testVariable', $list[0]->raw);
        self::assertSame("\nSingle text\n", $list[1]->raw);
        self::assertSame(' each items as item ', $list[2]->raw);
        self::assertSame("\n    ", $list[3]->raw);
        self::assertSame('item', $list[4]->raw);
        self::assertSame("\n", $list[5]->raw);
        self::assertSame('endeach', $list[6]->raw);
    }

    public function testUnclosedException(): void
    {
        $template = '{{test';
        $lexer = new DefaultLexer(new TemplatorConfig());
        self::expectException(LexerException::class);
        self::expectExceptionMessage('Unclosed tag "{{" found at position 0.');
        $lexer->tokenize($template);
    }

    public function testPlainText(): void
    {
        $template = 'Single text';
        $lexer = new DefaultLexer(new TemplatorConfig());
        $list = $lexer->tokenize($template);
        self::assertCount(1, $list);
        self::assertInstanceOf(TextToken::class, $list[0]);
        self::assertSame('Single text', $list[0]->raw);
    }

    public function testTokenizeWithDifferentTagLengths(): void
    {
        $config = new TemplatorConfig();
        $config->tokenCollection->upsert(
            new TokenDescriptor(
                '[[',
                ']]]',
                PrintToken::class,
            ),
        );

        $lexer = new DefaultLexer($config);

        $tokens = $lexer->tokenize('[[ content ]]]');

        self::assertCount(1, $tokens);
        self::assertInstanceOf(PrintToken::class, $tokens[0]);

        self::assertSame(' content ', $tokens[0]->raw);
    }
}
