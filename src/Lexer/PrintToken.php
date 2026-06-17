<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\TokenInterface;

/**
 * Токен выражения для вывода.
 *
 * Представляет собой конструкцию вида {{...}} в шаблоне.
 * Содержит выражение, которое должно быть вычислено и выведено в результирующий HTML.
 */
readonly class PrintToken implements TokenInterface
{
    /**
     * @param string $raw    исходное содержимое выражения (без разделителей, таких как "{{" и "}}")
     * @param int    $line   Строка в которой находится токен
     * @param int    $column Колонка в которой находится токен
     */
    public function __construct(public string $raw, public int $line, public int $column) {}
}
