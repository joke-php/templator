<?php

namespace Vasoft\Joke\Templator\Render\Handlers;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Contracts\Core\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\TagHandlerInterface;
use Vasoft\Joke\Templator\Exceptions\RenderingException;

class IfHandler extends BaseHandler implements TagHandlerInterface
{
    public function __construct()
    {
        $this->requiredAttributes = ['condition'];
    }

    /**
     * @inheritDoc
     * @throws RenderingException
     */
    protected function process(TagNode $node, array $context, RendererInterface $renderer): string
    {
        $conditionPath = $node->attributes['condition'];
        $value = $this->resolveValue($context, $conditionPath, false);

        if ($value) {
            return $renderer->render($node->children, $context);
        }

        return '';
    }
}