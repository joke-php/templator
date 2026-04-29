<?php

namespace Vasoft\Joke\Templator\Tests\Fixtures;

use Vasoft\Joke\Templator\Ast\TextNode;
use Vasoft\Joke\Templator\Contracts\Core\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\TagHandlerInterface;

class MockRenderer implements RendererInterface
{

    public array $renderedContexts = [];
    public string $lastOutput = '';

    public function registerTag(string $tagName, TagHandlerInterface $handler): static
    {
        // не используется в тестах обработчиков
        return $this;
    }

    public function render(array $nodes, array $context): string
    {
        $this->renderedContexts[] = $context;
        // Для тестов each/if — просто возвращаем placeholder
        return '[RENDERED_CHILDREN]';
    }

    public function optimizeStaticNodes(array $nodes, array $context): array
    {
        $this->renderedContexts[] = $context;
        // Для тестов each/if — просто возвращаем placeholder
        return [new TextNode('[RENDERED_CHILDREN]')];
    }
}