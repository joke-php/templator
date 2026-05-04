<?php

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

class IfHandler extends NodeHandler
{
    public function compile(NodeInterface $node, CompilerInterface $compiler, array $context, array $localVars = []): string
    {
        assert($node instanceof BlockNode);
        $result = "<?php if(" . $this->generateCondition($node->arguments) . "): ?>";
        $result .= $compiler->compile($node->children, $context);
        $result .= '<?php endif; ?>';
        return $result;
    }

    private function generateCondition(string $path): string
    {
        return "(bool)(" . $this->toPhpArrayAccess($path) . ")";
    }

    public function render(NodeInterface $node, CompilerInterface $compiler, array $context): string
    {
        assert($node instanceof BlockNode);

        $value = $this->resolveValue($context, $node->arguments, false);
        if ($value) {
            return $compiler->compile($node->children);
        }
        return '';
    }
}