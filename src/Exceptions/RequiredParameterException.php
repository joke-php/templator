<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Exceptions;

/**
 * Исключение, возникающее если не передан или имеет пустое значение обязательный параметр директивы.
 */
class RequiredParameterException extends TemplatorException
{
    public function __construct(string $directive, string $parameter, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Required parameter "%s" for directive "%s" is missing.', $parameter, $directive),
            $code,
            $previous,
        );
    }
}
