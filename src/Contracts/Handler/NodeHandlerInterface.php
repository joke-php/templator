<?php

namespace Vasoft\Joke\Templator\Contracts\Handler;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;

interface NodeHandlerInterface
{
    public function compile(NodeInterface $node, CompilerInterface $compiler, array $context, array $localVars = []): string;

    public function render(NodeInterface $node, CompilerInterface $compiler, array $context): string;
}