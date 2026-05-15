<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

/**
 * Интерфейс процессора узлов AST (Abstract Syntax Tree).
 *
 * Отвечает за обход дерева узлов и их преобразование в строковое представление.
 * В зависимости от реализации может выполнять компиляцию в PHP-код
 * или непосредственный рендеринг в HTML.
 */
interface NodeProcessorInterface
{
    /**
     * Обрабатывает массив узлов AST и возвращает результат их выполнения.
     *
     * @param list<NodeInterface>  $ast       массив корневых узлов абстрактного синтаксического дерева
     * @param array<string, mixed> $context   ассоциативный массив данных (контекст), доступных в шаблоне
     * @param list<string>         $localVars список имен переменных, которые считаются локальными
     *                                        (например, переменные цикла), для оптимизации доступа
     *
     * @return string Результат обработки.
     *                - Для компилятора: строка с PHP-кодом.
     *                - Для рендерера: готовая HTML-строка.
     */
    public function process(array $ast, array $context, array $localVars = []): string;
}
