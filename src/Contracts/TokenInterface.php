<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts;

/**
 * Токены AST.
 */
interface TokenInterface
{
    public string $raw {
        get;
    }
}
