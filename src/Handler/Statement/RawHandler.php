<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Statement;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;

/**
 * Обработчик директивы CSRF-токена.
 *
 * Отвечает за генерацию скрытого поля или вывод токена защиты от межсайтовой подделки запросов (CSRF).
 * Использует ServiceContainer для получения менеджера токенов и объекта запроса,
 * обеспечивая работу как в режиме компиляции, так и в режиме интерпретации.
 */
class RawHandler extends NodeHandler
{
    /**
     * Создает новый обработчик CSRF-директивы.
     *
     * @param ServiceContainer $serviceContainer контейнер зависимостей для доступа к сервисам безопасности
     */
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        if (!$node instanceof StatementNode) {
            throw new CompileException($this->getErrorMessage('StatementNode', $node));
        }

        $code = $this->compileVarAccess($node->arguments, $localVars);

        return '<?= ' . $code . '?>';
    }

    /**
     * {@inheritDoc}
     *
     * Извлекает значение из контекста и возвращает его, предварительно экранировав.
     *
     * @throws RenderingException если передан узел неверного типа
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        if (!$node instanceof StatementNode) {
            throw new RenderingException($this->getErrorMessage('StatementNode', $node));
        }

        return $this->resolveValue($context, $node->arguments, '', 'StatementNode');
    }
}
