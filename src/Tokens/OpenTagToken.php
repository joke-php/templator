<?php

namespace Vasoft\Joke\Templator\Tokens;

use Vasoft\Joke\Templator\Lexer\Token;

/**
 * @deprecated
 */
final class OpenTagToken extends Token
{
    /**
     * @param string $tagName Имя тега без префикса
     * @param string $fullTagName Имя тега с префиксом
     * @param array<string, string|bool> $attributes Атрибуты вида key => value
     * @param bool $static признак статического токена
     * @param string $raw Исходная строка тега
     */
    public function __construct(
        public string $tagName,
        public string $fullTagName,
        public array $attributes,
        string $raw,
        public bool $static,
    ) {
        parent::__construct($raw);
    }
}