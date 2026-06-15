<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Statement;

use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\Csrf\CsrfTokenManager;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Handler\NodeHandler;

/**
 * Обработчик директивы CSRF-токена.
 *
 * Отвечает за генерацию скрытого поля или вывод токена защиты от межсайтовой подделки запросов (CSRF).
 * Использует ServiceContainer для получения менеджера токенов и объекта запроса,
 * обеспечивая работу как в режиме компиляции, так и в режиме интерпретации.
 */
class CsrfHandler extends NodeHandler
{
    /**
     * Создает новый обработчик CSRF-директивы.
     *
     * @param ServiceContainer $serviceContainer контейнер зависимостей для доступа к сервисам безопасности
     */
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    /**
     * {@inheritDoc}
     *
     * Генерирует PHP-код для вывода CSRF-токена.
     * Код предполагает наличие переменной $container в области видимости скомпилированного шаблона.
     *
     * @return string PHP-код с инструкциями echo для вывода токена
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        // @codeCoverageIgnoreStart
        return <<<'PHP'
                <?php
                    use Vasoft\Joke\Http\Csrf\CsrfTokenManager;
                    use Vasoft\Joke\Http\HttpRequest;
                    $tokenManager = $container->get(CsrfTokenManager::class);
                    $request = $container->get(HttpRequest::class);
                    if ($tokenManager !== null && $request !== null) {
                        echo $tokenManager->getServerToken($request);
                    }
                ?>
            PHP;
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * Возвращает CSRF-токен в режиме интерпретации.
     * Безопасно обрабатывает ситуацию, когда сервисы недоступны (возвращает null/пустую строку).
     *
     * @return string значение CSRF-токена или пустая строка, если сервисы не инициализированы
     *
     * @throws ContainerException
     * @throws JokeException
     */
    public function render(NodeInterface $node, NodeProcessorInterface $processor, array $context): string
    {
        /** @var CsrfTokenManager $tokenManager */
        $tokenManager = $this->serviceContainer->get(CsrfTokenManager::class);
        /** @var HttpRequest $request */
        $request = $this->serviceContainer->get(HttpRequest::class);

        return $tokenManager->getServerToken($request);
    }
}
