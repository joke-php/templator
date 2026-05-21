<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * Обработчик узла вывода выражений.
 *
 * Отвечает за обработку конструкций вида {{expression}}.
 * Автоматически применяет экранирование htmlspecialchars (ENT_QUOTES, UTF-8)
 * ко всем выводимым данным для предотвращения XSS-атак.
 */
class PrintNodeHandler extends NodeHandler
{
    /**
     * Создает новый обработчик вывода.
     *
     * @param TemplatorConfig $config конфигурация шаблонизатора, содержащая настройки кодировки
     */
    public function __construct(
        protected readonly TemplatorConfig $config,
    ) {}

    /**
     * {@inheritDoc}
     *
     * Генерирует PHP-код для вывода значения.
     * Если переменная является локальной (находится в $localVars), генерируется
     * прямой доступ к переменной. В противном случае — доступ через массив контекста.
     *
     * @throws CompileException если передан узел неверного типа
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        if (!$node instanceof PrintNode) {
            throw new CompileException($this->getErrorMessage($node));
        }

        if (in_array($node->content, $localVars, true)) {
            $code = '$' . $node->content;
        } else {
            $code = $this->toPhpArrayAccess($node->content);
        }

        return "<?= htmlspecialchars((string){$code}, ENT_QUOTES, '{$this->config->encoding}');?>";
    }

    /**
     * {@inheritDoc}
     *
     * Извлекает значение из контекста и возвращает его, предварительно экранировав.
     *
     * @throws RenderingException если передан узел неверного типа
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        if (!$node instanceof PrintNode) {
            throw new RenderingException($this->getErrorMessage($node));
        }
        $value = $this->resolveValue($context, $node->content, '', 'PrintNode');

        return htmlspecialchars((string) $value, ENT_QUOTES, $this->config->encoding);
    }

    private function getErrorMessage(NodeInterface $node): string
    {
        return sprintf('Expected instance of PrintNode, got %s.', $node::class);
    }
}
