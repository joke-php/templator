<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\TemplatorConfig;
use Vasoft\Joke\Exceptions\JokeException;

/**
 * Обработчик узлов блоков с директивами.
 *
 * Выступает в роли диспетчера: определяет тип директивы (например, 'if', 'foreach')
 * и делегирует обработку соответствующему специализированному хендлеру.
 * Использует контейнер зависимостей для ленивой загрузки и кеширования экземпляров хендлеров.
 */
class BlockNodeHandler implements NodeHandlerInterface
{
    /** @var array<string,NodeHandlerInterface> */
    private array $instantiatedHandler = [];

    /**
     * Создает новый обработчик блоков.
     *
     * @param ServiceContainer $container контейнер зависимостей для получения хендлеров директив
     * @param TemplatorConfig  $config    конфигурация шаблонизатора, содержащая маппинг директив
     */
    public function __construct(
        protected readonly ServiceContainer $container,
        protected readonly TemplatorConfig $config,
    ) {}

    /**
     * {@inheritDoc}
     *
     * Делегирует компиляцию узла специализированному хендлеру директивы.
     *
     * @throws CompileException если передан узел неверного типа или хендлер директивы не найден
     * @throws JokeException    если хендлер не зарегистрирован в конфигурации
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        if (!$node instanceof BlockNode) {
            throw new CompileException($this->getErrorMessage('BlockNode', $node));
        }


        $handler = $this->getDirectiveHandler($node->directive);

        return $handler->compile($node, $processor, $context, $localVars);
    }

    /**
     * Получает экземпляр хендлера для указанной директивы.
     *
     * Использует внутренний кэш для повышения производительности. Если хендлер еще не создан,
     * регистрирует его в контейнере как синглтон и извлекает оттуда.
     *
     * @param string $directive имя директивы (например, 'if', 'foreach')
     *
     * @return NodeHandlerInterface экземпляр обработчика директивы
     *
     * @throws JokeException если хендлер не зарегистрирован в конфигурации
     */
    private function getDirectiveHandler(string $directive): NodeHandlerInterface
    {
        if (!isset($this->instantiatedHandler[$directive])) {
            $index = $directive . '#DirectiveHandler';
            if (!$this->container->has($index)) {
                $this->container->registerSingleton($index, $this->config->getDirectiveHandler($directive));
            }
            /** @var NodeHandlerInterface $handler */
            $handler = $this->container->get($index);
            $this->instantiatedHandler[$directive] = $handler;
        }

        return $this->instantiatedHandler[$directive];
    }

    /**
     * {@inheritDoc}
     *
     * Делегирует рендеринг узла специализированному хендлеру директивы.
     *
     * @throws RenderingException если передан узел неверного типа или хендлер директивы не найден
     * @throws JokeException      если хендлер не зарегистрирован в конфигурации
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        if (!$node instanceof BlockNode) {
            throw new RenderingException($this->getErrorMessage('BlockNode', $node));
        }

        $handler = $this->getDirectiveHandler($node->directive);

        return $handler->render($node, $processor, $context);
    }

    /**
     * Формирует сообщение об ошибке несоответствия типа узла.
     *
     * @param string        $expected ожидаемое имя класса узла
     * @param NodeInterface $node     фактически полученный узел
     *
     * @return string текст ошибки
     */
    protected function getErrorMessage(string $expected, NodeInterface $node): string
    {
        return sprintf('Expected instance of %s, got %s.', $expected, $node::class);
    }
}
