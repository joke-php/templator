<?php

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;

class PrintNodeHandler extends NodeHandler
{
    public function compile(
        NodeInterface $node,
        CompilerInterface $compiler,
        array $context,
        array $localVars = []
    ): string {
        assert($node instanceof PrintNode);
        if (in_array($node->content, $localVars, true)) {
            $code = '$' . $node->content;
        } else {
            $path = $this->toPhpArrayAccess($node->content);
            $code = "htmlspecialchars((string)$path, ENT_QUOTES, 'UTF-8')";
        }

        return "<?= " . $code . "?>";
    }

    public function render(NodeInterface $node, CompilerInterface $compiler, array $context): string
    {
        assert($node instanceof PrintNode);

        return $this->resolveValue($context, $node->content, '');
    }
}