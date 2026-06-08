<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * Обработчик узлов блоков с блочными директивами.
 *
 * Выступает в роли диспетчера: определяет тип директивы (например, 'if', 'foreach')
 * и делегирует обработку соответствующему специализированному хендлеру.
 * Использует контейнер зависимостей для ленивой загрузки и кеширования экземпляров хендлеров.
 */
class BlockNodeHandler extends StatementNodeHandler implements NodeHandlerInterface
{
    /**
     * Создает новый обработчик блоков.
     *
     * @param ServiceContainer $container контейнер зависимостей для получения хендлеров директив
     * @param TemplatorConfig  $config    конфигурация шаблонизатора, содержащая маппинг директив
     */
    public function __construct(
        ServiceContainer $container,
        TemplatorConfig $config,
    ) {
        parent::__construct($container, $config);
        $this->nodeClass = BlockNode::class;
    }
}
