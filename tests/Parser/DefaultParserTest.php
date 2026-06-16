<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Parser;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Exceptions\ParserException;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\DefaultParser;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\Branch;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Parser\DefaultParser
 */
final class DefaultParserTest extends TestCase
{
    public function testParse(): void
    {
        $tokens = [
            new PrintToken('testVariable'),
            new TextToken("\nSingle text\n"),
            new StatementToken(' if expression1 '),
            new TextToken('branch1'),
            new StatementToken('elseif expression2'),
            new TextToken('branch2'),
            new StatementToken('/if'),
            new StatementToken('csrf'),
        ];

        $ast = new DefaultParser(new TemplatorConfig())->parse($tokens);

        self::assertCount(4, $ast);
        self::assertInstanceOf(PrintNode::class, $ast[0]);
        self::assertSame('testVariable', $ast[0]->content);
        self::assertInstanceOf(TextNode::class, $ast[1]);
        self::assertSame("\nSingle text\n", $ast[1]->content);
        self::assertInstanceOf(BlockNode::class, $ast[2]);
        self::assertSame('if', $ast[2]->directive);
        self::assertSame('expression1', $ast[2]->arguments);
        self::assertCount(1, $ast[2]->children);
        $child = $ast[2]->children[0];
        self::assertInstanceOf(TextNode::class, $child);
        self::assertSame('branch1', $child->content);
        self::assertCount(1, $ast[2]->branches);
        $branch = $ast[2]->branches[0];
        self::assertInstanceOf(Branch::class, $branch);
        self::assertSame('elseif', $branch->directive);
        self::assertSame('expression2', $branch->arguments);
        self::assertCount(1, $branch->children);
        $child = $branch->children[0];
        self::assertInstanceOf(TextNode::class, $child);
        self::assertSame('branch2', $child->content);
        self::assertInstanceOf(StatementNode::class, $ast[3]);
        self::assertSame('csrf', $ast[3]->directive);
    }

    public function testParseUnclosed(): void
    {
        $tokens = [
            new StatementToken(' if expression1 '),
            new StatementToken(' if expression2 '),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unclosed tag(s): 'if, if'.");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnexpectedEnd(): void
    {
        $tokens = [
            new StatementToken('/if'),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected end tag: '/if'.");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testMismatchedTag(): void
    {
        $tokens = [
            new StatementToken('if expression1'),
            new StatementToken('/foreach'),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Mismatched block: expected end of 'if', got '/foreach'");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnexpectedBranch(): void
    {
        $tokens = [
            new StatementToken('foreach items as item'),
            new StatementToken('elseif expression2'),
            new StatementToken('/foreach'),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected branch 'elseif'.");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnknownDirective(): void
    {
        $tokens = [
            new StatementToken('dir-unknown'),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unknown directive: 'dir-unknown'.");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnexpectedBranchOutside(): void
    {
        $tokens = [
            new StatementToken('elseif expression2'),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected branch 'elseif'.");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }
}
