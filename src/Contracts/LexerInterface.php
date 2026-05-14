<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts;

/**
 * Интерфейс лексера.
 */
interface LexerInterface
{
    /**
     * Преобразует строку в список токенов.
     *
     * @param string $template исходный текст
     *
     * @return array<TokenInterface> список токенов
     */
    public function tokenize(string $template): array;
}
