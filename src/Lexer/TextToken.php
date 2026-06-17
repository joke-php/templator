<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\TokenInterface;

/**
 * Токен статического текста.
 *
 * Представляет собой фрагмент шаблона, не содержащий специальных конструкций
 * (директив или выражений). Это обычный HTML-код или текст, который должен
 * быть выведен в результат без изменений.
 */
readonly class TextToken implements TokenInterface
{
    /**
     * @param string $raw    исходный текстовый контент
     * @param int    $line   Строка в которой находится токен
     * @param int    $column Колонка в которой находится токен
     */
    public function __construct(public string $raw, public int $line, public int $column) {}
}
