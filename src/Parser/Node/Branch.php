<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Представление ветви блочной директивы (например, else или elseif).
 *
 * Используется внутри BlockNode для хранения альтернативных блоков кода.
 * Каждая ветвь имеет имя (тип ветвления) и опциональные аргументы,
 * а также собственный список дочерних узлов AST.
 */
class Branch
{
    /**
     * Список дочерних узлов, принадлежащих данной ветви.
     *
     * @var list<NodeInterface>
     */
    public array $children = [];

    /**
     * Создает новую ветвь блока.
     *
     * @param class-string $tokenClass класс токена
     * @param string       $directive  имя ветви (например, 'else', 'elseif')
     * @param string       $arguments  аргументы ветви (используется для условий в elseif)
     */
    public function __construct(
        public readonly string $tokenClass,
        public readonly string $directive,
        public readonly string $arguments = '',
    ) {}
}
