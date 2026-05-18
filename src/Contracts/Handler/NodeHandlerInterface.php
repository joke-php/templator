<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts\Handler;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;

/**
 * Интерфейс обработчика узла AST.
 *
 * Отвечает за логику обработки конкретного типа узла.
 * Реализует два режима работы:
 * 1. Компиляция: преобразование узла в фрагмент PHP-кода.
 * 2. Рендеринг: непосредственное выполнение логики узла и возврат HTML-строки.
 *
 * Используется для обхода дерева шаблона.
 */
interface NodeHandlerInterface
{
    /**
     * Компилирует узел в строку PHP-кода.
     *
     * Генерирует код, который будет выполнен при последующем подключении скомпилированного шаблона.
     * Для рекурсивной обработки дочерних узлов следует использовать метод $processor->process().
     *
     * @param NodeInterface          $node      узел AST для компиляции
     * @param NodeProcessorInterface $processor процессор для рекурсивной обработки дочерних элементов
     * @param array<string, mixed>   $context   данные контекста
     * @param list<string>           $localVars список локальных переменных (например, переменные цикла), доступных в текущей области
     *
     * @return string фрагмент PHP-кода
     *
     * @throws CompileException При ошибках компиляции
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string;

    /**
     * Рендерит узел в HTML-строку (режим интерпретации).
     *
     * Выполняет логику узла "на лету" без генерации промежуточного PHP-файла.
     *
     * @param NodeInterface          $node      узел AST для рендеринга
     * @param NodeProcessorInterface $processor процессор для рекурсивной обработки дочерних элементов
     * @param array<string, mixed>   $context   ассоциативный массив данных шаблона
     *
     * @return string готовый HTML-фрагмент
     *
     * @throws RenderingException При ошибках рендера
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string;
}
