<?php

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Блочный узел дерева
 */
class BlockNode extends StatementNode implements NodeInterface
{
    public array $children = [];
}