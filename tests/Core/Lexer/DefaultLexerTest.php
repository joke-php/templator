<?php

namespace Vasoft\Joke\Templator\Tests\Core\Lexer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Core\Container\TemplateContainer;
use Vasoft\Joke\Templator\Core\Lexer\DefaultLexer;
use Vasoft\Joke\Templator\Core\Lexer\TextToken;

#[Group("skip")]
class DefaultLexerTest extends TestCase
{
    private static ?DefaultLexer $defaultLexer = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $container = new TemplateContainer();
        print_r($container->tokenDescriptors->list());
        self::$defaultLexer = new DefaultLexer($container);

    }

    public function testTextOnly(): void
    {

        $tokens = self::$defaultLexer->tokenize('Hello world');
        self::assertCount(1, $tokens);
        self::assertInstanceOf(TextToken::class, $tokens[0]);
        self::assertSame('Hello world', $tokens[0]->raw);
    }

    public function testMultipleTokens(): void
    {
        $tokens = self::$defaultLexer->tokenize('{{test}}Hello world {%example%}{% /example %} {{ test }}');
        self::assertCount(6, $tokens);
    }
}
