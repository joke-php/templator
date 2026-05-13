<?php

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

class EachHandler extends NodeHandler
{
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);

        [$valueVar, $keyVar, $path] = $this->parseArguments($node->arguments);

        $arrayAccess = $this->toPhpArrayAccess($path);
        $localVars = [$valueVar];
        if ($keyVar !== '') {
            $localVars[] = $keyVar;
        }
        if ($keyVar !== '') {
            $result = "<?php foreach ({$arrayAccess} as \${$keyVar} => \${$valueVar}): ?>";
        } else {
            $result = "<?php foreach ({$arrayAccess} as \${$valueVar}): ?>";
        }

        $result .= $processor->process($node->children, $context, $localVars);
        $result .= '<?php endforeach; ?>';
        return $result;
    }

    private function parseArguments(string $value): array
    {
        if (!preg_match('#^\s*(\w+)(?:\s*,\s*(\w+))?\s+in\s+(.+)$#', $value, $matches)) {
            throw new CompileException('Invalid foreach syntax: {$value}');
        }
        $keyVar = $matches[1];
        $valueVar = $matches[2] ?? '';
        $path = trim($matches[3]);

        if ($valueVar === '') {
            $valueVar = $keyVar;
            $keyVar = null;
        }
        return [$valueVar, $keyVar, $path];
    }

    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);
        [$valueVar, $keyVar, $path] = $this->parseArguments($node->arguments);

        $items = $this->resolveValue($context, $path, []);
        $output = '';
        foreach ($items as $index => $item) {
            $iterationContext = $context;
            $iterationContext[$valueVar] = $item;
            if ($keyVar !== '') {
                $iterationContext[$keyVar] = $index;
            }
            $output .= $processor->process($node->children, $iterationContext, $localVars);
        }
        return $output;
    }
}