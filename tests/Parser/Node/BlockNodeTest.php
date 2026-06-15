<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Parser\Node;

use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Parser\Node\TextNode;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Parser\Node\BlockNode
 */
final class BlockNodeTest extends TestCase
{
    public function testBranches(): void
    {
        $childMain = new TextNode('childMain');
        $childSecond1 = new TextNode('childSecond1');
        $childSecond2 = new TextNode('childSecond2');
        $childLast = new TextNode('childLast');
        $node = new BlockNode('example', 'args');
        $node->addChild($childMain);
        $node->openBranch('second', 'args2');
        $node->addChild($childSecond1);
        $node->addChild($childSecond2);
        $node->openBranch('last', 'args3');
        $node->addChild($childLast);

        self::assertCount(1, $node->children);
        self::assertSame('childMain', $node->children[0]->content);

        self::assertSame('second', $node->branches[0]->name);
        self::assertCount(2, $node->branches[0]->children);
        self::assertSame('childSecond1', $node->branches[0]->children[0]->content);
        self::assertSame('childSecond2', $node->branches[0]->children[1]->content);

        self::assertSame('last', $node->branches[1]->name);
        self::assertCount(1, $node->branches[1]->children);
        self::assertSame('childLast', $node->branches[1]->children[0]->content);
    }
}
