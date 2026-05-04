<?php

namespace Vasoft\Joke\Templator\Contracts\Ast;

use Vasoft\Joke\Templator\Exceptions\RenderingException;

interface RendererInterface
{
    /**
     * Регистрирует обработчик для тега.
     *
     * @param string $tagName Имя тега (без префикса)
     * @param TagHandlerInterface $handler
     * @return RendererInterface
     */
    public function registerTag(string $tagName, TagHandlerInterface $handler): static;

    /**
     * Рендерит AST в строку.
     *
     * @param array<NodeInterface> $nodes список узлов
     * @param array<string, mixed> $context Переменные шаблона
     * @return string
     * @throws RenderingException
     */
    public function optimizeStaticNodes(array $nodes, array $context): array;
}