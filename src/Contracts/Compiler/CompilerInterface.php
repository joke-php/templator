<?php

namespace Vasoft\Joke\Templator\Contracts\Compiler;

use Vasoft\Joke\Templator\Contracts\Ast\NodeInterface;

/**
 * Компилятор AST дерева в PHP код
 */
interface CompilerInterface
{
    /**
     * @param $ast array<NodeInterface> AST дерево
     * @return string
     */
    public function compile(array $ast): string;

    /**
     * @param string $tagName имя тега
     * @param string $compilerClass класс компилятора тега
     * @return $this
     */
    public function registerTagCompiler(string $tagName, string $compilerClass): static;
    /**
     * @param string $tagClass класс ноды
     * @param string $compilerClass класс компилятора тега
     * @return $this
     */
    public function registerNodeCompiler(string $nodeClass, string $compilerClass): static;
}