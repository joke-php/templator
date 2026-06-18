<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Render;

use Vasoft\Joke\Templator\AbstractNodeProcessor;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;

/**
 * Стандартный рендерер шаблонов.
 *
 * Наследует базовую логику обхода AST от AbstractNodeProcessor и специализирует её
 * для немедленного выполнения (интерпретации) шаблона. При обработке каждого узла
 * вызывает метод 'render' у соответствующего хендлера.
 *
 * Результатом работы процессора является готовая строка вывода (например, HTML),
 * полученная в результате выполнения логики шаблона "на лету".
 */
class DefaultRenderer extends AbstractNodeProcessor implements NodeProcessorInterface
{
    /**
     * {@inheritDoc}
     *
     * Возвращает имя метода хендлера, отвечающего за рендеринг узла.
     *
     * @return non-empty-string строка 'render'
     */
    protected function getHandlerMethodName(): string
    {
        return 'render';
    }
}
