<?php

namespace Vasoft\Joke\Templator\Render;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Ast\TextNode;
use Vasoft\Joke\Templator\Contracts\Core\Ast\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\TagHandlerInterface;
use Vasoft\Joke\Templator\Exceptions\RenderingException;

class DefaultRenderer implements RendererInterface
{
    /**
     * @var array<string, TagHandlerInterface>
     */
    private array $handlers = [];

    /**
     * @inheritDoc
     */
    public function registerTag(string $tagName, TagHandlerInterface $handler): static
    {
        $this->handlers[$tagName] = $handler;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws RenderingException
     */
    public function optimizeStaticNodes(array $nodes, array $context): array
    {
        foreach ($nodes as $i => $node) {
            if ($node instanceof TagNode) {
                if ($node->static) {
                    $nodes[$i] = new TextNode($this->renderNode($node, $context));
                    continue;
                }
                $node->children = $this->optimizeStaticNodes($node->children, $context);
            }
        }
        return $nodes;
    }


    /**
     * @param NodeInterface $node
     * @param array<string, mixed> $context
     * @return string
     * @throws RenderingException
     */
    private function renderNode(TagNode $node, array $context): string
    {
        if (!isset($this->handlers[$node->tagName])) {
            throw new RenderingException(
                "No handler registered for tag '{$node->fullTagName}'."
            );
        }
        $handler = $this->handlers[$node->tagName];
        return $handler->handle($node, $context, $this);
    }
}