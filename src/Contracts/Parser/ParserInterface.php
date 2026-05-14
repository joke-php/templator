<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts\Parser;

use Vasoft\Joke\Templator\Contracts\TokenInterface;
use Vasoft\Joke\Templator\Exceptions\ParserException;

interface ParserInterface
{
    /**
     * Строит AST из списка токенов.
     *
     * @param array<TokenInterface> $tokens список токенов
     *
     * @return array<NodeInterface> Корневые узлы
     *
     * @throws ParserException При синтаксической ошибке
     */
    public function parse(array $tokens): array;
}
