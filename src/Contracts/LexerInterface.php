<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts;

/**
 * Интерфейс лексического анализатора.
 *
 * Отвечает за первичную обработку исходного кода шаблона.
 * Разбивает входную строку на последовательность смысловых единиц — токенов,
 * выделяя специальные конструкции (директивы, выражения) и текстовые блоки.
 */
interface LexerInterface
{
    /**
     * Преобразует строку в список токенов.
     *
     * @param string $template исходный текст
     *
     * @return list<TokenInterface> список токенов
     */
    public function tokenize(string $template): array;
}
