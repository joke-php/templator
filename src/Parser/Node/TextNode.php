<?php

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

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