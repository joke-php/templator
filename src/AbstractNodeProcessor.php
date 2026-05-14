<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

abstract class AbstractNodeProcessor implements NodeProcessorInterface
{
    /** @var array<string, NodeHandlerInterface> */
    private array $instantiatedNodeHandler = [];

    public function __construct(
        private readonly ServiceContainer $container,
        private readonly TemplatorConfig $config,
    ) {}

    public function process(array $ast, array $context, array $localVars = []): string
    {
        $code = '';
        foreach ($ast as $node) {
            $code .= $this->processNode($node, $context, $localVars);
        }

        return $code;
    }

    protected function processNode(NodeInterface $node, array $context, array $localVars = []): string
    {
        $handler = $this->getNodeHandler($node::class);

        return $this->executeNodeHandler($node, $handler, $context, $localVars);
    }

    abstract protected function executeNodeHandler(
        NodeInterface $node,
        NodeHandlerInterface $handler,
        array $context,
        array $localVars = [],
    ): string;

    private function getNodeHandler(string $nodeClass): NodeHandlerInterface
    {
        if (!isset($this->instantiatedNodeHandler[$nodeClass])) {
            $index = $nodeClass . '#Handler';
            if (!$this->container->has($index)) {
                $this->container->registerSingleton($index, $this->config->getNodeHandler($nodeClass));
            }
            $this->instantiatedNodeHandler[$nodeClass] = $this->container->get($index);
        }

        return $this->instantiatedNodeHandler[$nodeClass];
    }
}
