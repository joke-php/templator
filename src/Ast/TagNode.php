<?php

namespace Vasoft\Joke\Templator\Ast;

use Vasoft\Joke\Templator\Contracts\Core\Ast\NodeInterface;

/**
 * Узел тега AST дерева
 */
class TagNode implements NodeInterface
{
    /**
     * @param string $tagName Имя узла, без префикса шаблонизатора
     * @param string $fullTagName Полное имя с префиксом шаблонизатора
     * @param array<string,string|bool> $attributes Атрибут узла в виде key=>value
     * @param array $children Дочерние узлы
     * @param bool $selfClosing Признак самозакрывающегося тега
     * @param bool $static Признак статического узла
     */
    public function __construct(
        public string $tagName,
        public string $fullTagName,
        public array $attributes = [],
        public array $children = [],
        public bool $selfClosing = false,
        public bool $static = false
    ) {
    }
}