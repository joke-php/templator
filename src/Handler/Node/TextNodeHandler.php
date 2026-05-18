<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Parser\Node\TextNode;

/**
 * Обработчик узла текстового контента (TextNode).
 *
 * Отвечает за обработку статического текста в шаблоне.
 * Поскольку текст не требует вычислений или экранирования, обработчик
 * просто возвращает исходное содержимое узла как в режиме компиляции,
 * так и в режиме рендеринга.
 */
class TextNodeHandler implements NodeHandlerInterface
{
    /**
     * {@inheritDoc}
     *
     * Возвращает текстовое содержимое без изменений.
     *
     * @throws CompileException если передан узел неверного типа
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        if (!$node instanceof TextNode) {
            throw new CompileException($this->getErrorMessage($node));
        }

        return $node->content;
    }

    /**
     * {@inheritDoc}
     *
     * Возвращает текстовое содержимое без изменений.
     *
     * @throws RenderingException если передан узел неверного типа
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        if (!$node instanceof TextNode) {
            throw new RenderingException($this->getErrorMessage($node));
        }

        return $node->content;
    }

    private function getErrorMessage(NodeInterface $node): string
    {
        return sprintf('Expected instance of TextNode, got %s.', $node::class);
    }
}
