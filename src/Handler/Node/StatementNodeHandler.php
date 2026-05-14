<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

class StatementNodeHandler implements NodeHandlerInterface
{
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);

        return 'Compile';
    }

    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);
        echo __METHOD__, PHP_EOL;

        return 'Render';
    }
}
