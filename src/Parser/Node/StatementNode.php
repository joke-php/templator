<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Базовый узел AST для инструкций (директив).
 *
 * Представляет собой элемент шаблона, заключенный в теги директив (например, {% ... %}).
 * Служит основой для более сложных узлов, таких как BlockNode (блочные директивы)
 * или может использоваться самостоятельно для одиночных инструкций.
 *
 * Хранит имя директивы и ее необработанные аргументы для последующей обработки.
 */
class StatementNode implements NodeInterface
{
    /**
     * Создает новый узел инструкции.
     *
     * @param string $directive имя директивы (например, 'if', 'foreach', 'include')
     * @param string $arguments строка аргументов, переданных директиве
     */
    public function __construct(
        public string $directive,
        public string $arguments,
    ) {}

}
