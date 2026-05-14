<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\TokenInterface;

/**
 * Описание токена.
 */
final readonly class TokenDescriptor
{
    /** @var int Длина открывающего тега */
    public int $openLength;
    /** @var int Длина закрывающего тега */
    public int $closeLength;

    /**
     * @param string                       $open       Открывающий тег
     * @param string                       $close      Закрывающий тег
     * @param class-string<TokenInterface> $tokenClass Класс токена
     */
    public function __construct(
        public string $open,
        public string $close,
        public string $tokenClass,
    ) {
        $this->openLength = strlen($this->open);
        $this->closeLength = strlen($this->close);
    }
}
