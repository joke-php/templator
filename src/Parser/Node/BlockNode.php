<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Узел блочной директивы.
 *
 * Контейнер для дочерних узлов и ветвлений (elseif, else).
 * Используется для директив, имеющих тело и закрывающий тег (например, {% if %}, {% foreach %}).
 *
 * Поддерживает структуру с основным телом (children) и дополнительными ветвями (branches)
 */
class BlockNode extends StatementNode implements NodeInterface
{
    /**
     * Список дочерних узлов основного тела блока.
     * Заполняется до появления первой ветви (else/elseif) или если ветвей нет вообще.
     *
     * @var list<NodeInterface>
     */
    public private(set) array $children = [];
    /**
     * Список ветвей блока (например, elseif, else).
     * Каждая ветвь содержит свои дочерние узлы и метаданные (имя, аргументы).
     *
     * @var list<Branch>
     */
    public private(set) array $branches = [];

    /**
     * Создает новый узел блочной директивы.
     *
     * @param string $directive имя директивы (например, 'if', 'foreach')
     * @param string $arguments аргументы директивы
     */
    public function __construct(string $directive, string $arguments)
    {
        parent::__construct($directive, $arguments);
    }

    /**
     * Добавляет дочерний узел в текущий контекст блока.
     *
     * Если активна ветвь (elseif/else), узел добавляется в неё.
     * В противном случае узел добавляется в основное тело блока.
     *
     * @param NodeInterface $child добавляемый узел AST
     */
    public function addChild(NodeInterface $child): void
    {
        if ([] === $this->branches) {
            $this->children[] = $child;
        } else {
            $branch = array_last($this->branches);
            $branch->children[] = $child;
        }
    }

    /**
     * Открывает новую ветвь блока (например, при встрече {% else %} или {% elseif %}).
     *
     * Создает новый объект Branch, добавляет его в список и переключает контекст
     * добавления детей на эту новую ветвь.
     *
     * @param string $branchName имя ветви (например, 'else', 'elseif')
     * @param string $argument   аргументы ветви (для elseif)
     */
    public function openBranch(string $branchName, string $argument = ''): void
    {
        $branch = new Branch($branchName, $argument);
        $this->branches[] = $branch;
    }
}
