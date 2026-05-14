<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Compiler;

use Vasoft\Joke\Templator\AbstractNodeProcessor;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

class DefaultCompiler extends AbstractNodeProcessor implements NodeProcessorInterface
{
    protected function executeNodeHandler(
        NodeInterface $node,
        NodeHandlerInterface $handler,
        array $context,
        array $localVars = [],
    ): string {
        return $handler->compile($node, $this, $context, $localVars);
    }
}
