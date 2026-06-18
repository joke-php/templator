<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Абстрактный базовый процессор узлов AST.
 *
 * Реализует шаблонный метод для обхода дерева узлов.
 * Отвечает за инициализацию, кэширование хендлеров и делегирование обработки
 * конкретным реализациям через динамический вызов методов.
 */
abstract class AbstractNodeProcessor implements NodeProcessorInterface
{
    /**
     * Кэш экземпляров хендлеров узлов.
     *
     * @var array<string, NodeHandlerInterface>
     */
    private array $instantiatedNodeHandler = [];

    /**
     * Создает новый процессор узлов.
     *
     * @param ServiceContainer $container контейнер зависимостей для получения хендлеров
     * @param TemplatorConfig  $config    конфигурация шаблонизатора
     */
    public function __construct(
        private readonly ServiceContainer $container,
        private readonly TemplatorConfig $config,
    ) {}

    /**
     * {@inheritDoc}
     *
     * Последовательно обрабатывает каждый узел в переданном AST и конкатенирует результаты.
     *
     * @param array<NodeInterface> $ast       массив корневых узлов абстрактного синтаксического дерева
     * @param array<string, mixed> $context   данные контекста шаблона
     * @param list<string>         $localVars список локальных переменных текущей области видимости
     *
     * @return string результат обработки всех узлов (скомпилированный код или отрендеренный HTML)
     *
     * @throws ContainerException        если не удалось получить сервис из контейнера
     * @throws TemplatorException        если хендлер узла не найден или имеет неверный тип
     * @throws ParameterResolveException если возникла ошибка при разрешении параметров сервиса
     */
    public function process(array $ast, array $context, array $localVars = []): string
    {
        $code = '';
        foreach ($ast as $node) {
            $code .= $this->processNode($node, $context, $localVars);
        }

        return $code;
    }

    /**
     * Возвращает имя метода хендлера, который должен быть вызван для обработки узла.
     *
     * Этот метод определяет режим работы процессора (компиляция или рендеринг).
     *
     * @return non-empty-string имя метода (например, 'compile' или 'render')
     */
    abstract protected function getHandlerMethodName(): string;

    /**
     * Обрабатывает отдельный узел AST.
     *
     * Получает соответствующий хендлер для класса узла и вызывает у него метод,
     * определенный в getHandlerMethodName().
     *
     * @param NodeInterface        $node      узел для обработки
     * @param array<string, mixed> $context   данные контекста шаблона
     * @param list<string>         $localVars список локальных переменных
     *
     * @return string результат обработки конкретного узла
     *
     * @throws ContainerException        если не удалось получить сервис из контейнера
     * @throws TemplatorException        если хендлер узла не найден или имеет неверный тип
     * @throws ParameterResolveException если возникла ошибка при разрешении параметров сервиса
     */
    protected function processNode(NodeInterface $node, array $context, array $localVars = []): string
    {
        $handler = $this->getNodeHandler($node::class);
        $handlerMethod = $this->getHandlerMethodName();

        return $handler->{$handlerMethod}($node, $this, $context, $localVars);
    }

    /**
     * Получает экземпляр хендлера для указанного класса узла.
     *
     * Использует внутреннее кэширование. Если хендлер еще не создан, регистрирует его
     * в контейнере как синглтон на основе конфигурации.
     *
     * @param class-string<NodeInterface> $nodeClass класс узла AST
     *
     * @return NodeHandlerInterface экземпляр обработчика узла
     *
     * @throws TemplatorException        если хендлер не зарегистрирован в конфигурации или не реализует интерфейс
     * @throws ContainerException        если возникла ошибка контейнера зависимостей
     * @throws ParameterResolveException если возникла ошибка при разрешении параметров сервиса
     */
    private function getNodeHandler(string $nodeClass): NodeHandlerInterface
    {
        if (!isset($this->instantiatedNodeHandler[$nodeClass])) {
            $index = $nodeClass . '#Handler';
            if (!$this->container->has($index)) {
                $this->container->registerSingleton($index, $this->config->getNodeHandler($nodeClass));
            }
            $handler = $this->container->get($index);
            if (!$handler instanceof NodeHandlerInterface) {
                throw new TemplatorException(
                    sprintf(
                        "Invalid node handler '%s' for '%s'.",
                        $handler ? $handler::class : 'unknown',
                        $nodeClass,
                    ),
                );
            }
            $this->instantiatedNodeHandler[$nodeClass] = $handler;
        }

        return $this->instantiatedNodeHandler[$nodeClass];
    }
}
