<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\TemplatorConfig;

class BlockNodeHandler implements NodeHandlerInterface
{
    /** @var array<string,NodeHandlerInterface> */
    private array $instantiatedHandler = [];

    public function __construct(
        protected readonly ServiceContainer $container,
        protected readonly TemplatorConfig $config,
    ) {}

    /**
     * @inherit
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);

        $handler = $this->getDirectiveHandler($node->directive);

        return $handler->compile($node, $processor, $context);
    }

    private function getDirectiveHandler(string $directive): NodeHandlerInterface
    {
        if (!isset($this->instantiatedHandler[$directive])) {
            $index = $directive . '#DirectiveHandler';
            if (!$this->container->has($index)) {
                $this->container->registerSingleton($index, $this->config->getDirectiveHandler($directive));
            }
            $handler = $this->container->get($index);
            assert($handler instanceof NodeHandlerInterface);
            $this->instantiatedHandler[$directive] = $handler;
        }

        return $this->instantiatedHandler[$directive];
    }

    /**
     * @inherit
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);
        $handler = $this->getDirectiveHandler($node->directive);

        return $handler->render($node, $processor, $context, $localVars);
    }
}
