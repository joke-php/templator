<?php

namespace Vasoft\Joke\Templator\Render\Handlers;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Contracts\Core\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\TagHandlerInterface;
use Vasoft\Joke\Templator\Exceptions\RenderingException;

abstract class BaseHandler implements TagHandlerInterface
{
    /**
     * @var list<string> Аттрибуты которые должны быть и значение их не может быть пустым
     */
    protected array $requiredAttributes = [];

    /**
     * @inheritDoc
     */
    final public function handle(TagNode $node, array $context, RendererInterface $renderer): string
    {
        $this->verifyAttributes($node);
        return $this->process($node, $context, $renderer);
    }

    /**
     * Непосредственная обработка тега
     * @param TagNode $node Обрабатываемый тег
     * @param array<string, mixed> $context Переменные контекста
     * @param RendererInterface $renderer Рендерер
     * @return string
     */
    abstract protected function process(TagNode $node, array $context, RendererInterface $renderer): string;

    /**
     * Получает значение из контекста по точечной нотации
     *
     * Простая поддержка точечной нотации: user.name → $context['user']['name']
     * @param array<string, mixed> $context контекст
     * @param string $path Путь переменной, разделенный точками
     * @param mixed $defaultValue значение возвращаемое если значение не найдено
     * @return mixed
     */
    protected function resolveValue(array $context, string $path, mixed $defaultValue): mixed
    {
        $value = $context;
        foreach (explode('.', $path) as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return $defaultValue;
            }
        }
        return $value;
    }

    private function verifyAttributes(TagNode $node): void
    {
        foreach ($this->requiredAttributes as $attribute) {
            if (empty($node->attributes[$attribute])) {
                throw new RenderingException(
                    "Attribute '{$attribute}' is required for <{$node->fullTagName}> and must be not empty"
                );
            }
        }
    }
}