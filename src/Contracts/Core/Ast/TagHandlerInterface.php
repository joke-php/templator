<?php

namespace Vasoft\Joke\Templator\Contracts\Core\Ast;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Exceptions\RenderingException;

interface TagHandlerInterface
{
    /**
     * Обрабатывает узел тега и возвращает HTML-фрагмент.
     *
     * @param TagNode $node Узел AST
     * @param array<string, mixed> $context Переменные шаблона
     * @param RendererInterface $renderer Для рекурсивного рендеринга дочерних узлов
     * @return string
     * @throws RenderingException
     */
    public function handle(TagNode $node, array $context, RendererInterface $renderer): string;
}