<?php

namespace Vasoft\Joke\Templator\Render\Handlers;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Contracts\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Ast\TagHandlerInterface;

class EchoHandler extends BaseHandler implements TagHandlerInterface
{
    public function __construct()
    {
        $this->requiredAttributes = ['value'];
    }

    protected function process(TagNode $node, array $context, RendererInterface $renderer): string
    {
        $value = $this->resolveValue($context, $node->attributes['value'], '');
        print_r([
            $node,
            $context,
            $value,
        ]);
        if ($value === null || $value === false || $value === '') {
            return '';
        }
        return ($node->attributes['escaped'] ?? false)
            ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')
            : (string)$value;
    }
}