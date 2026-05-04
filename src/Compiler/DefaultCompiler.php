<?php

namespace Vasoft\Joke\Templator\Compiler;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\NodeCompilerInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\TemplatorConfig;

class DefaultCompiler implements CompilerInterface
{
    public bool $renderMode = false {
        get {
            return $this->renderMode;
        }
        set {
            $this->renderMode = $value;
        }
    }

    /** @var array<string, NodeCompilerInterface> $instantiatedNodeCompilers */
    private array $instantiatedNodeHandler = [];

    public function __construct(
        private readonly ServiceContainer $container,
        private readonly TemplatorConfig $config,
    ) {
    }

    public function compile(array $ast, array $context, array $localVars = []): string
    {
        $code = '';
        foreach ($ast as $node) {
            $code .= $this->compileNode($node, $context, $localVars);
        }
        return $code;
    }

    public function compileNode(NodeInterface $node, array $context, array $localVars = []): string
    {
        $compiler = $this->getNodeHandler($node::class);
        return $this->renderMode
            ? $compiler->render($node, $this, $context)
            : $compiler->compile($node, $this, $context, $localVars);
    }

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