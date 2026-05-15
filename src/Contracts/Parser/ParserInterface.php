<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts\Parser;

use Vasoft\Joke\Templator\Contracts\TokenInterface;
use Vasoft\Joke\Templator\Exceptions\ParserException;

/**
 * Интерфейс синтаксического анализатора.
 *
 * Отвечает за преобразование линейной последовательности токенов, полученной от лексера,
 * в иерархическую структуру — абстрактное синтаксическое дерево (AST).
 * Выполняет проверку синтаксической корректности шаблона (соответствие открывающих и закрывающих тегов,
 * валидность вложенности директив).
 */
interface ParserInterface
{
    /**
     * Парсит массив токенов и строит AST.
     *
     * @param list<TokenInterface> $tokens упорядоченный список токенов, полученный от лексера
     *
     * @return list<NodeInterface> массив корневых узлов построенного абстрактного синтаксического дерева
     *
     * @throws ParserException если обнаружена синтаксическая ошибка (например, незакрытый тег или неверная вложенность)
     */
    public function parse(array $tokens): array;
}
