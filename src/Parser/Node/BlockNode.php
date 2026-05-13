<?php

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Блочный узел дерева
 */
class BlockNode extends StatementNode implements NodeInterface
{
    public private(set) array $children = [];

    public private(set) array $branches = [];
    private ?array $currentBuffer = null;


    public function __construct(string $directive, string $arguments)
    {
        parent::__construct($directive, $arguments);
        $this->currentBuffer = &$this->children;
    }

    public function addChild(NodeInterface $child): void
    {
        $this->currentBuffer[] = $child;
    }

    public function openBranch(string $branchName, ?string $argument = null): void
    {
        $branch = new Branch($branchName, $argument);
        $this->branches[] = $branch;
        $this->currentBuffer = &$branch->children;
    }
}