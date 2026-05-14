<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;

/**
 * @todo работа с localvars
 * @todo экранирование при рендере
 */
class PrintNodeHandler extends NodeHandler
{
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof PrintNode);
        if (in_array($node->content, $localVars, true)) {
            $code = '$' . $node->content;
        } else {
            $path = $this->toPhpArrayAccess($node->content);
            $code = "htmlspecialchars((string){$path}, ENT_QUOTES, 'UTF-8')";
        }

        return '<?= ' . $code . '?>';
    }

    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        assert($node instanceof PrintNode);

        return $this->resolveValue($context, $node->content, '');
    }
}
