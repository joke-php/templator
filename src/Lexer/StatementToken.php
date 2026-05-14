<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

class StatementToken extends Token
{
    public function getDirective(): string
    {
        return explode(' ', trim($this->raw), 2)[0];
    }

    public function getArguments(): string
    {
        $parts = explode(' ', trim($this->raw), 2);

        return $parts[1] ?? '';
    }
}
