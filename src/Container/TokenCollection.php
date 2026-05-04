<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Container;

use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;

class TokenCollection
{
    /**
     * @var array<string,TokenDescriptor> Реестр описаний токенов
     */
    private array $descriptors = [];

    /**
     * Добавляет описание токена с проверкой на существование
     * @param TokenDescriptor $descriptor Описание токена
     * @return $this
     * @throws TemplatorException Если токен с таким открывающим маркером уже существует
     */
    public function add(TokenDescriptor $descriptor): static
    {
        if (array_key_exists($descriptor->open, $this->descriptors)) {
            throw new TemplatorException('Token already exists');
        }
        $this->descriptors[$descriptor->open] = $descriptor;
        return $this;
    }

    /**
     * Безусловное добавляет описание токена. Если токен с таким же открывающим маркером был уже зарегистрирован, то перезаписывается
     * @param TokenDescriptor $descriptor Описание токена
     * @return $this
     */
    public function upsert(TokenDescriptor $descriptor): static
    {
        $this->descriptors[$descriptor->open] = $descriptor;
        return $this;
    }


    /**
     * Полная замена списка
     * @param list<TokenDescriptor> $items
     * @return $this
     */
    public function reset(array $items): static
    {
        $this->descriptors = [];
        foreach ($items as $descriptor) {
            $this->descriptors[$descriptor->open] = $descriptor;
        }

        return $this;
    }

    /**
     * Возвращает список описаний токенов
     * @return array<string,TokenDescriptor>
     */
    public function list(): array
    {
        return $this->descriptors;
    }
}