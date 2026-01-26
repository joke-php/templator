<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Core\Container;

use Vasoft\Joke\Templator\Core\Lexer\PrintToken;
use Vasoft\Joke\Templator\Core\Lexer\StatementToken;
use Vasoft\Joke\Templator\Core\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Коллекция для хранения токенов и подготовки списка для работы лексера
 * по умолчанию регистрируются токены вывода и стейта
 */
class TokenCollection
{
    /**
     * @var array<string,TokenDescriptor> Реестр описаний токенов
     */
    private array $descriptors = [];

    public function __construct()
    {
        $this->initDefault();
    }

    protected function initDefault(): void
    {
        $this->descriptors['{{'] = new TokenDescriptor('{{', '}}', PrintToken::class);
        $this->descriptors['{%'] = new TokenDescriptor('{%', '%}', StatementToken::class);
    }

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
     * @param list<TokenDescriptor>|array<string,TokenDescriptor> $items
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