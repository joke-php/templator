<?php

namespace Vasoft\Joke\Templator\Tests\Unit\Ast;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Ast\DefaultParser;
use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Ast\TextNode;
use Vasoft\Joke\Templator\Exceptions\ParserException;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;

#[Group("skip")]
class DefaultParserTest extends TestCase
{
    private static ?DefaultParser $parser = null;
    private static ?DefaultLexer $lexer = null;

    public static function setUpBeforeClass(): void
    {
        self::$parser = new DefaultParser();
        self::$lexer = new DefaultLexer();
        parent::setUpBeforeClass();
    }

    public function testParseTextOnly(): void
    {
        $tokens = self::$lexer->tokenize('Hello world');
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        self::assertInstanceOf(TextNode::class, $ast[0]);
        self::assertSame('Hello world', $ast[0]->content);
    }

    public function testParseSelfClosingTag(): void
    {
        $tokens = self::$lexer->tokenize('<j-echo value="name"/>');
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        self::assertInstanceOf(TagNode::class, $ast[0]);

        /** @var TagNode $node */
        $node = $ast[0];
        self::assertSame('echo', $node->tagName);
        self::assertSame(['value' => 'name'], $node->attributes);
        self::assertTrue($node->selfClosing);
        self::assertEmpty($node->children);
    }

    public function testParseNestedTags(): void
    {
        $template = '<j-if condition="user"><j-echo value="name"/></j-if>';
        $tokens = self::$lexer->tokenize($template);
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        self::assertInstanceOf(TagNode::class, $ast[0]);

        /** @var TagNode $ifNode */
        $ifNode = $ast[0];
        self::assertSame('if', $ifNode->tagName);
        self::assertFalse($ifNode->selfClosing);
        self::assertCount(1, $ifNode->children);

        /** @var TagNode $echoNode */
        $echoNode = $ifNode->children[0];
        self::assertSame('echo', $echoNode->tagName);
        self::assertTrue($echoNode->selfClosing);
    }

    public function testParseMixedContent(): void
    {
        $template = 'Start<j-if><j-echo/></j-if>End';
        $tokens = self::$lexer->tokenize($template);
        $ast = self::$parser->parse($tokens);

        self::assertCount(3, $ast);
        self::assertInstanceOf(TextNode::class, $ast[0]);
        self::assertInstanceOf(TagNode::class, $ast[1]);
        self::assertInstanceOf(TextNode::class, $ast[2]);

        self::assertSame('Start', $ast[0]->content);
        self::assertSame('End', $ast[2]->content);
    }

    public function testParseBooleanAttribute(): void
    {
        $tokens = self::$lexer->tokenize('<j-if visible/>');
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        /** @var TagNode $node */
        $node = $ast[0];
        self::assertSame(['visible' => true], $node->attributes);
    }

    public function testMismatchedClosingTag(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Mismatched closing tag: expected '</j-if>' but found '</j-for>'");

        $tokens = self::$lexer->tokenize('<j-if></j-for>');
        self::$parser->parse($tokens);
    }

    public function testUnexpectedClosingTag(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unexpected closing tag '</j-if>' with no matching open tag");

        $tokens = self::$lexer->tokenize('</j-if>');
        self::$parser->parse($tokens);
    }

    public function testUnclosedTag(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage("Unclosed tag(s): j-if, j-for");

        $tokens = self::$lexer->tokenize('<j-if><j-for>');
        self::$parser->parse($tokens);
    }

    public function testEmptyTemplate(): void
    {
        $tokens = self::$lexer->tokenize('');
        $ast = self::$parser->parse($tokens);

        self::assertEmpty($ast);
    }

    public function testDeepNesting(): void
    {
        $template = '<j-a><j-b><j-c><j-d/></j-c></j-b></j-a>';
        $tokens = self::$lexer->tokenize($template);
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        $a = $ast[0];
        $b = $a->children[0];
        $c = $b->children[0];
        $d = $c->children[0];

        self::assertSame('a', $a->tagName);
        self::assertSame('b', $b->tagName);
        self::assertSame('c', $c->tagName);
        self::assertSame('d', $d->tagName);
        self::assertTrue($d->selfClosing);
    }

    public function testTextInsideTagIsAddedToChildren(): void
    {
        $template = '<j-div>Hello world</j-div>';
        $tokens = self::$lexer->tokenize($template);
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        self::assertInstanceOf(TagNode::class, $ast[0]);

        /** @var TagNode $divNode */
        $divNode = $ast[0];
        self::assertSame('div', $divNode->tagName);
        self::assertCount(1, $divNode->children);
        self::assertInstanceOf(TextNode::class, $divNode->children[0]);
        self::assertSame('Hello world', $divNode->children[0]->content);
    }

    public function testMixedContentInsideTag(): void
    {
        $template = '<j-wrapper>Start<j-echo value="x"/>End</j-wrapper>';
        $tokens = self::$lexer->tokenize($template);
        $ast = self::$parser->parse($tokens);

        self::assertCount(1, $ast);
        /** @var TagNode $wrapper */
        $wrapper = $ast[0];
        self::assertSame('wrapper', $wrapper->tagName);
        self::assertCount(3, $wrapper->children);

        self::assertInstanceOf(TextNode::class, $wrapper->children[0]);
        self::assertSame('Start', $wrapper->children[0]->content);

        self::assertInstanceOf(TagNode::class, $wrapper->children[1]);
        self::assertSame('echo', $wrapper->children[1]->tagName);

        self::assertInstanceOf(TextNode::class, $wrapper->children[2]);
        self::assertSame('End', $wrapper->children[2]->content);
    }
}

