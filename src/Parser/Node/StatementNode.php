<?php

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Блочный узел дерева
 */
class StatementNode implements NodeInterface
{
    /**
     * @param string $directive Имя директивы
     * @param string $arguments Аргументы директивы
     */
    public function __construct(
        public string $directive,
        public string $arguments,
    ) {
    }

}