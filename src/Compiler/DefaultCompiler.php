<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Compiler;

use Vasoft\Joke\Templator\AbstractNodeProcessor;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;

/**
 * Стандартный компилятор шаблонов.
 *
 * Наследует базовую логику обхода AST от AbstractNodeProcessor и специализирует её
 * для генерации PHP-кода. При обработке каждого узла вызывает метод 'compile'
 * у соответствующего хендлера.
 *
 * Результатом работы процессора является строка PHP-кода, готовая к выполнению
 * или сохранению в кэш.
 */
class DefaultCompiler extends AbstractNodeProcessor implements NodeProcessorInterface
{
    /**
     * {@inheritDoc}
     *
     * Возвращает имя метода хендлера, отвечающего за компиляцию узла.
     *
     * @return non-empty-string строка 'compile'
     */
    protected function getHandlerMethodName(): string
    {
        return 'compile';
    }
}
