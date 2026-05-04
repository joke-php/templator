<?php

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\TemplatorConfig;

class BlockNodeHandler implements NodeHandlerInterface
{
    private array $instantiatedHandler = [];

    public function __construct(
        protected readonly ServiceContainer $container,
        protected readonly TemplatorConfig $config
    ) {
    }

    public function compile(NodeInterface $node, CompilerInterface $compiler, array $context, array $localVars = []): string
    {
        assert($node instanceof BlockNode);

        $handler = $this->getDirectiveHandler($node->directive);
        return $handler->compile($node, $compiler, $context);
    }

    private function getDirectiveHandler(string $directive): NodeHandlerInterface
    {
        if (!isset($this->instantiatedHandler[$directive])) {
            $index = $directive . '#DirectiveHandler';
            if (!$this->container->has($index)) {
                $this->container->registerSingleton($index, $this->config->getDirectiveHandler($directive));
            }
            $this->instantiatedHandler[$directive] = $this->container->get($index);
        }
        return $this->instantiatedHandler[$directive];
    }

    public function render(NodeInterface $node, CompilerInterface $compiler, array $context): string
    {
        assert($node instanceof BlockNode);
        $handler = $this->getDirectiveHandler($node->directive);
        return $handler->render($node, $compiler, $context);
    }

    private function generateArrayAccess(string $path): string
    {
        $keys = explode('.', $path);
        $code = '$context';
        foreach ($keys as $key) {
            $code .= "['" . addslashes($key) . "']";
        }
        return $code;
    }

    private function getValue(string $path, array $context): string
    {
        $keys = explode('.', $path);
        $current = $context;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $current)) {
                return '';
            } elseif (is_array($current[$key])) {
                $current = $current[$key];
            } else {
                return (string)$current[$key];
            }
        }
        return empty($current) ? '' : "Array";
    }
}