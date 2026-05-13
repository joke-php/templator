<?php

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Parser\Node\TextNode;

class TextNodeHandler implements NodeHandlerInterface
{
    public function compile(NodeInterface $node, NodeProcessorInterface $processor, array $context, array $localVars = []): string
    {
        assert($node instanceof TextNode);
        return $node->content;
    }

    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = []
    ): string
    {
        assert($node instanceof TextNode);
        return $node->content;
    }
}