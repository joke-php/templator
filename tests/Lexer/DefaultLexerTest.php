<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Lexer;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Exceptions\LexerException;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\TemplatorConfig;
use Vasoft\Joke\Templator\TemplatorProvider;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Lexer\DefaultLexer
 */
final class DefaultLexerTest extends TestCase
{
    private static TemplatorConfig $config;

    public static function setUpBeforeClass(): void
    {
        $container = new ServiceContainer();
        self::$config = new TemplatorConfig();
        $container->registerSingleton(TemplatorConfig::class, self::$config);
        $provider = new TemplatorProvider($container);
        $provider->boot();
    }

    public function testLexer(): void
    {
        $template = <<<'HTML'
            {{testVariable}}
            Single text
            {% each items as item %}
                {{item}}{%endeach%}
            HTML;
        $lexer = new DefaultLexer(self::$config);
        $list = $lexer->tokenize($template);

        self::assertCount(6, $list);
        self::assertInstanceOf(PrintToken::class, $list[0]);
        self::assertSame('testVariable', $list[0]->raw);
        self::assertSame(1, $list[0]->line);
        self::assertSame(1, $list[0]->column);

        self::assertInstanceOf(TextToken::class, $list[1]);
        self::assertSame("\nSingle text\n", $list[1]->raw);
        self::assertSame(1, $list[1]->line);
        self::assertSame(17, $list[1]->column);

        self::assertInstanceOf(StatementToken::class, $list[2]);
        self::assertSame(' each items as item ', $list[2]->raw);
        self::assertSame(3, $list[2]->line);
        self::assertSame(1, $list[2]->column);

        self::assertInstanceOf(TextToken::class, $list[3]);
        self::assertSame("\n    ", $list[3]->raw);
        self::assertSame(3, $list[3]->line);
        self::assertSame(25, $list[3]->column);

        self::assertInstanceOf(PrintToken::class, $list[4]);
        self::assertSame('item', $list[4]->raw);
        self::assertSame(4, $list[4]->line);
        self::assertSame(5, $list[4]->column);

        self::assertInstanceOf(StatementToken::class, $list[5]);
        self::assertSame('endeach', $list[5]->raw);
        self::assertSame(4, $list[5]->line);
        self::assertSame(13, $list[5]->column);
    }

    public function testUnclosedException(): void
    {
        $template = '{{test';
        $lexer = new DefaultLexer(self::$config);
        self::expectException(LexerException::class);
        self::expectExceptionMessage('Unclosed tag "{{" found at position 1:1.');
        $lexer->tokenize($template);
    }

    public function testNewLineInTag(): void
    {
        $template = "{%foreach lines\n as line %}\n{{test";
        $lexer = new DefaultLexer(self::$config);
        self::expectException(LexerException::class);
        self::expectExceptionMessage('Unclosed tag "{{" found at position 3:1.');
        $lexer->tokenize($template);
    }

    public function testPlainText(): void
    {
        $template = 'Single text';
        $lexer = new DefaultLexer(self::$config);
        $list = $lexer->tokenize($template);
        self::assertCount(1, $list);
        self::assertInstanceOf(TextToken::class, $list[0]);
        self::assertSame('Single text', $list[0]->raw);
    }

    public function testTokenizeWithDifferentTagLengths(): void
    {
        $config = new TemplatorConfig();
        $container = new ServiceContainer();
        $container->registerSingleton(TemplatorConfig::class, $config);
        $provider = new TemplatorProvider($container);
        $provider->boot();

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
