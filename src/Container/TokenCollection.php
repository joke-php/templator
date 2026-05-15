<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Container;

use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;

/**
 * Коллекция дескрипторов токенов.
 *
 * Хранит реестр правил лексического анализа, сопоставляя открывающие разделители
 * с их закрывающими аналогами и соответствующими классами токенов.
 * Используется лексером для поиска и классификации конструкций в шаблоне.
 */
class TokenCollection
{
    /**
     * Реестр дескрипторов токенов, индексированный по открывающему разделителю.
     *
     * @var array<string, TokenDescriptor>
     */
    private array $descriptors = [];

    /**
     * Добавляет новый дескриптор токена.
     *
     * Проверяет уникальность открывающего разделителя. Если токен с таким же
     * открывающим маркером уже зарегистрирован, выбрасывается исключение.
     *
     * @param TokenDescriptor $descriptor объект, описывающий правила токена
     *
     * @return $this
     *
     * @throws TemplatorException если токен с указанным открывающим маркером уже существует
     */
    public function add(TokenDescriptor $descriptor): static
    {
        if (array_key_exists($descriptor->open, $this->descriptors)) {
            throw new TemplatorException('Token with marker "' . $descriptor->open . '" already exists.');
        }
        $this->descriptors[$descriptor->open] = $descriptor;

        return $this;
    }

    /**
     * Добавляет или обновляет дескриптор токена.
     *
     * Если токен с таким открывающим маркером уже существует, он будет заменен новым.
     * Полезно для переопределения стандартных токенов или настройки конфигурации "на лету".
     *
     * @param TokenDescriptor $descriptor объект, описывающий правила токена
     *
     * @return $this
     */
    public function upsert(TokenDescriptor $descriptor): static
    {
        $this->descriptors[$descriptor->open] = $descriptor;

        return $this;
    }

    /**
     * Полностью заменяет текущий список дескрипторов новым.
     *
     * Очищает существующий реестр и заполняет его переданными элементами.
     *
     * @param list<TokenDescriptor> $items массив новых дескрипторов
     *
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
     * Возвращает полный список зарегистрированных дескрипторов.
     *
     * @return array<string, TokenDescriptor> ассоциативный массив, где ключ — открывающий разделитель
     */
    public function list(): array
    {
        return $this->descriptors;
    }
}
