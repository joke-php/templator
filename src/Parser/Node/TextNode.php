<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Текстовый узел AST дерева.
 */
class TextNode implements NodeInterface
{
    /**
     * @param class-string $tokenClass класс токена
     * @param string       $content    Текст
     */
    public function __construct(public readonly string $tokenClass, public string $content) {}

}
