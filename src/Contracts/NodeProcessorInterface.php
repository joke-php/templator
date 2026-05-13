<?php

namespace Vasoft\Joke\Templator\Contracts;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Компилятор AST дерева в PHP код
 */
interface NodeProcessorInterface
{
    /**
     * @param $ast array<NodeInterface> AST дерево
     * @return string
     */
    public function process(array $ast, array $context, array $localVars = []): string;
}