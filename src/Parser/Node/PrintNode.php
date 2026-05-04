<?php

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Узел AST дерева для вывода значения
 */
class PrintNode implements NodeInterface
{
    /**
     * @param string $content Переменная для вывода
     */
    public function __construct(public string $content) { }
}