<?php

namespace Vasoft\Joke\Templator\Contracts\Compiler;

use Vasoft\Joke\Templator\Contracts\Ast\NodeInterface;

interface NodeCompilerInterface
{
    /**
     * Поддерживает ли компиляцию для ноды
     * @param NodeInterface $node
     * @return bool
     */
    public function supports(NodeInterface $node): bool;

    /**
     * Компиляция ноды
     * @param NodeInterface $node
     * @param CompilerInterface $compiler
     * @return string
     */
    public function compile(NodeInterface $node, CompilerInterface $compiler): string;
}