<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\TokenInterface;

/**
 * Токен директивы или утверждения.
 *
 * Представляет собой конструкцию вида {% ... %} в шаблоне.
 * Используется для управляющих структур (if, foreach) и других инструкций.
 * Предоставляет методы для разбора имени директивы и её аргументов.
 */
readonly class StatementToken implements TokenInterface
{
    /**
     * @param string $raw    исходное содержимое директивы (без разделителей {% и %})
     * @param int    $line   Строка в которой находится токен
     * @param int    $column Колонка в которой находится токен
     */
    public function __construct(public string $raw, public int $line, public int $column) {}

    /**
     * Извлекает имя директивы из содержимого токена.
     *
     * Возвращает первое слово до первого пробела.
     * Например, для строки "if user.active" вернет "if".
     *
     * @return string имя директивы (например, 'if', 'foreach', 'else')
     */
    public function getDirective(): string
    {
        return explode(' ', trim($this->raw), 2)[0];
    }

    /**
     * Извлекает аргументы директивы.
     *
     * Возвращает всё содержимое после первого пробела.
     * Например, для строки "foreach item in items" вернет "item in items".
     * Если аргументов нет, возвращает пустую строку.
     *
     * @return string строка аргументов или пустая строка, если их нет
     */
    public function getArguments(): string
    {
        $parts = explode(' ', trim($this->raw), 2);

        return $parts[1] ?? '';
    }
}
