<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\TokenInterface;

/**
 * Описание токена.
 *
 * Конфигурационный объект, описывающий правила распознавания конкретного типа токена.
 * Содержит открывающий и закрывающий разделители, а также класс токена, который должен
 * быть создан при обнаружении соответствия в шаблоне.
 *
 * Используется лексером для эффективного поиска и классификации конструкций в исходном коде.
 */
final readonly class TokenDescriptor
{
    /** @var int Длина открывающего тега */
    public int $openLength;
    /** @var int Длина закрывающего тега */
    public int $closeLength;

    /**
     * Создает новый дескриптор токена.
     *
     * @param string                       $open       открывающий разделитель (например, '{{' или '{%')
     * @param string                       $close      закрывающий разделитель (например, '}}' или '%}')
     * @param class-string<TokenInterface> $tokenClass полное имя класса, реализующего TokenInterface, для создания экземпляра токена
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
