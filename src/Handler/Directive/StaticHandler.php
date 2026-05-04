<?php

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

class StaticHandler extends NodeHandler
{
    public function compile(NodeInterface $node, CompilerInterface $compiler, array $context, array $localVars = []): string
    {
        assert($node instanceof BlockNode);
        $backupMode = $compiler->renderMode;
        $compiler->renderMode = true;
        $result = $compiler->compile($node->children, $context);
        $compiler->renderMode = $backupMode;
        return $result;
    }

    public function render(NodeInterface $node, CompilerInterface $compiler, array $context): string
    {
        return $this->compile($node, $compiler, $context);
    }
}