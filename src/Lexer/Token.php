<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\TokenInterface;

/**
 * Абстрактный Класс токенов.
 *
 * Базовый класс, на случай если понадобиться реализовать общие методы
 */
abstract class Token implements TokenInterface
{
    public function __construct(public readonly string $raw) {}
}
