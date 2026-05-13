<?php

namespace Vasoft\Joke\Templator\Render;

use Vasoft\Joke\Templator\AbstractNodeProcessor;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

class DefaultRenderer extends AbstractNodeProcessor implements NodeProcessorInterface
{

    protected function executeNodeHandler(
        NodeInterface $node,
        NodeHandlerInterface $handler,
        array $context,
        array $localVars = []
    ): string {
        return $handler->render($node, $this, $context, $localVars);
    }
}