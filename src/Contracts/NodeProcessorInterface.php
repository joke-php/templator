<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Компилятор AST дерева в PHP код.
 */
interface NodeProcessorInterface
{
    /**
     * @param $ast array<NodeInterface> AST дерево
     */
    public function process(array $ast, array $context, array $localVars = []): string;
}
