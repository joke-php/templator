<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Блочный узел дерева.
 */
class BlockNode extends StatementNode implements NodeInterface
{
    /**
     * @var list<NodeInterface>
     */
    public private(set) array $children = [];
    /**
     * @var list<Branch>
     */
    public private(set) array $branches = [];

    private int $currentBranch = -1;

    public function __construct(string $directive, string $arguments)
    {
        parent::__construct($directive, $arguments);
    }

    public function addChild(NodeInterface $child): void
    {
        if (-1 === $this->currentBranch) {
            $this->children[] = $child;
        } else {
            assert(isset($this->branches[$this->currentBranch]));
            $this->branches[$this->currentBranch]->children[] = $child;
        }
    }

    public function openBranch(string $branchName, ?string $argument = null): void
    {
        $branch = new Branch($branchName, $argument);
        $this->branches[] = $branch;
        ++$this->currentBranch;
    }
}
