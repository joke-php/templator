<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Templator\Container\DirectiveCollection;
use Vasoft\Joke\Templator\Container\TokenCollection;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Parser\Node\TextNode;

/**
 * Конфигурация шаблонизатора Joke.
 */
class TemplatorConfig extends AbstractConfig
{
    /**
     * Коллекция дескрипторов токенов для лексического анализа.
     * Определяет открывающие/закрывающие маркеры и соответствующие классы токенов.
     */
    public private(set) readonly TokenCollection $tokenCollection;
    /**
     * Коллекция правил синтаксиса директив для парсера.
     * Определяет типы директив (BEGIN, END, BRANCH, SINGLE) и их взаимосвязи.
     */
    public private(set) readonly DirectiveCollection $directiveCollection;
    /**
     * Кодировка вывода шаблонов.
     * По умолчанию 'UTF-8'. Может быть изменена до заморозки конфига.
     */
    public private(set) string $encoding {
        get => $this->encoding ??= 'UTF-8';
    }
    /**
     * Реестр обработчиков директив.
     * Маппинг: имя директивы -> класс обработчика.
     *
     * @var array<string, class-string<NodeHandlerInterface>>
     */
    private array $directiveHandler = [];
    /**
     * Реестр обработчиков узлов AST.
     * Маппинг: класс узла -> класс обработчика.
     *
     * @var array<class-string<NodeInterface>, class-string<NodeHandlerInterface>>
     */
    private array $nodeHandler = [];

    /**
     * Создает конфигурацию и инициализирует стандартные настройки.
     */
    public function __construct()
    {
        $this->tokenCollection = new TokenCollection();
        $this->directiveCollection = new DirectiveCollection();
    }

    /**
     * Регистрирует обработчик для указанной директивы.
     *
     * @param string                             $directive имя директивы (например, 'if', 'csrf')
     * @param class-string<NodeHandlerInterface> $handler   класс обработчика директивы
     *
     * @return $this для цепочки вызовов
     */
    public function addDirectiveHandler(string $directive, string $handler): static
    {
        $this->directiveHandler[$directive] = $handler;

        return $this;
    }

    /**
     * Регистрирует обработчик для указанного класса узла AST.
     *
     * @param class-string<NodeInterface>        $nodeClass класс узла (например, TextNode::class)
     * @param class-string<NodeHandlerInterface> $handler   класс обработчика узла
     *
     * @return $this для цепочки вызовов
     */
    public function addNodeHandler(string $nodeClass, string $handler): static
    {
        $this->nodeHandler[$nodeClass] = $handler;

        return $this;
    }

    /**
     * Возвращает класс обработчика для указанного узла AST.
     *
     * @param class-string<NodeInterface> $nodeClass класс узла
     *
     * @return class-string<NodeHandlerInterface> класс обработчика
     *
     * @throws TemplatorException если обработчик для данного класса узла не зарегистрирован
     */
    public function getNodeHandler(string $nodeClass): string
    {
        if (!isset($this->nodeHandler[$nodeClass])) {
            throw new TemplatorException("Handler for '{$nodeClass}' not found.");
        }

        return $this->nodeHandler[$nodeClass];
    }

    /**
     * Возвращает класс обработчика для указанной директивы.
     *
     * @param string $directive имя директивы
     *
     * @return class-string<NodeHandlerInterface> класс обработчика
     *
     * @throws TemplatorException если обработчик для данной директивы не зарегистрирован
     */
    public function getDirectiveHandler(string $directive): string
    {
        if (!isset($this->directiveHandler[$directive])) {
            throw new TemplatorException("Handler for directive '{$directive}' not found.");
        }

        return $this->directiveHandler[$directive];
    }

    /**
     * Устанавливает кодировку вывода шаблонов.
     *
     * @param string $encoding кодировка (например, 'UTF-8', 'Windows-1251')
     *
     * @return $this для цепочки вызовов
     *
     * @throws ConfigException если конфигурация уже заморожена
     */
    public function setEncoding(string $encoding): static
    {
        $this->guard();
        $this->encoding = $encoding;

        return $this;
    }
}
