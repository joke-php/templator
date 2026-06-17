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
            new PrintToken('testVariable', 1, 1),
            new TextToken("\nSingle text\n", 2, 2),
            new StatementToken(' if expression1 ', 3, 1),
            new TextToken('branch1', 3, 20),
            new StatementToken('elseif expression2', 4, 5),
            new TextToken('branch2', 6, 1),
            new StatementToken('/if', 7, 1),
            new StatementToken('csrf', 8, 1),
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
            new StatementToken(' if expression1 ', 1, 1),
            new StatementToken(' if expression2 ', 2, 1),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unclosed tag(s): 'if, if'.");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnexpectedEnd(): void
    {
        $tokens = [
            new StatementToken('/if', 10, 11),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected end tag: '/if' (10:11).");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testMismatchedTag(): void
    {
        $tokens = [
            new StatementToken('if expression1', 1, 10),
            new StatementToken('/foreach', 18, 4),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Mismatched block: expected end of 'if', got '/foreach' (18:4)");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnexpectedBranch(): void
    {
        $tokens = [
            new StatementToken('foreach items as item', 1, 1),
            new StatementToken('elseif expression2', 1, 40),
            new StatementToken('/foreach', 5, 7),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected branch 'elseif' (1:40).");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnknownDirective(): void
    {
        $tokens = [
            new StatementToken('dir-unknown', 5, 1),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unknown directive: 'dir-unknown' (5:1).");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }

    public function testUnexpectedBranchOutside(): void
    {
        $tokens = [
            new StatementToken('elseif expression2', 7, 1),
        ];
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected branch 'elseif' (7:1).");
        new DefaultParser(new TemplatorConfig())->parse($tokens);
    }
}
