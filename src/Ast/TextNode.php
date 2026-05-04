<?php

namespace Vasoft\Joke\Templator\Ast;

use Vasoft\Joke\Templator\Contracts\Ast\NodeInterface;

/**
 * Текстовый узел AST дерева
 */
class TextNode implements NodeInterface
{
    /**
     * @param string $content Текст
     */
    public function __construct(public string $content) { }
}