<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

class IfHandler extends NodeHandler
{
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);
        $result = '<?php if(' . $this->generateCondition($node->arguments) . '): ?>';
        $result .= $processor->process($node->children, $context, $localVars);
        foreach ($node->branches as $branch) {
            if ('else' === $branch->name) {
                $result .= $this->compileElse($processor, $branch->children, $context, $localVars);
            } elseif ('elseif' === $branch->name) {
                $result .= $this->compileElseIf(
                    $processor,
                    $branch->arguments,
                    $branch->children,
                    $context,
                    $localVars,
                );
            }
        }
        $result .= '<?php endif; ?>';

        return $result;
    }

    private function compileElse(
        NodeProcessorInterface $processor,
        array $children,
        array $context,
        array $localVars,
    ): string {
        $result = '<?php else: ?>';

        return $result . $processor->process($children, $context, $localVars);
    }

    private function compileElseIf(
        NodeProcessorInterface $processor,
        string $arguments,
        array $children,
        array $context,
        array $localVars,
    ): string {
        $result = '<?php elseif(' . $this->generateCondition($arguments) . '): ?>';

        return $result . $processor->process($children, $context, $localVars);
    }

    private function generateCondition(string $path): string
    {
        return '(bool)(' . $this->toPhpArrayAccess($path) . ')';
    }

    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof BlockNode);

        $value = $this->resolveValue($context, $node->arguments, false);
        if ($value) {
            return $processor->process($node->children, $context, $localVars);
        }
        foreach ($node->branches as $branch) {
            if ('else' === $branch->name) {
                return $processor->process($branch->children, $context, $localVars);
            }
            if ('elseif' === $branch->name) {
                $value = $this->resolveValue($context, $branch->arguments, false);
                if ($value) {
                    return $processor->process($branch->children, $context, $localVars);
                }
            }
        }

        return '';
    }
}
