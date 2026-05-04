<?php

namespace Vasoft\Joke\Templator\Contracts\Compiler;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Компилятор AST дерева в PHP код
 */
interface CompilerInterface
{
    public bool $renderMode {
        get;
        set;
    }

    /**
     * @param $ast array<NodeInterface> AST дерево
     * @return string
     */
    public function compile(array $ast, array $context, array $localVars = []): string;
}