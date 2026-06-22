<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Узел AST дерева для вывода значения.
 */
class PrintNode implements NodeInterface
{
    /**
     * @param class-string $tokenClass класс токена
     * @param string       $content    Переменная для вывода
     */
    public function __construct(public readonly string $tokenClass, public string $content) {}
}
