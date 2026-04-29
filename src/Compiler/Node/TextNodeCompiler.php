<?php

namespace Vasoft\Joke\Templator\Compiler\Node;

use Vasoft\Joke\Templator\Ast\TextNode;
use Vasoft\Joke\Templator\Contracts\Core\Ast\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\NodeCompilerInterface;

class TextNodeCompiler implements NodeCompilerInterface
{

    /**
     * @inheritDoc
     */
    public function supports(NodeInterface $node): bool
    {
        return $node instanceof TextNode;
    }

    /**
     * @inheritDoc
     * @param TextNode $node
     */
    public function compile(NodeInterface $node, CompilerInterface $compiler): string
    {
        return 'echo ' . var_export($node->content, true) . ";\n";
    }
}