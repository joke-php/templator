<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Statement;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;

/**
 * Обработчик директивы {%raw expression%}.
 *
 * Выводит значение переменной БЕЗ HTML-экранирования.
 * Используется для вывода заранее подготовленного HTML-контента,
 * например содержимого каркаса через {%raw __layout.content%}.
 *
 * Для обычного вывода с экранированием используйте {{ expression }}.
 *
 * @see StatementNode Ожидаемый тип узла AST
 */
class RawHandler extends NodeHandler
{
    /**
     * Компилирует директиву raw в PHP-echo без экранирования.
     *
     * Генерирует код вида: <?= $variable ?>
     * В отличие от {{ }}, НЕ оборачивает вывод в htmlspecialchars().
     *
     * @param StatementNode          $node      Узел выражения {%raw var.name%}
     * @param NodeProcessorInterface $processor Процессор узлов (не используется,
     *                                          но требуется интерфейсом)
     * @param array<string, mixed>   $context   Контекст переменных шаблона
     * @param list<string>           $localVars Локальные переменные цикла/блока
     *
     * @return string PHP-код echo без экранирования
     *
     * @throws CompileException Если передан узел неверного типа
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        // @phpstan-ignore instanceof.alwaysTrue
        if (!$node instanceof StatementNode) {
            throw new CompileException($this->getErrorMessage('StatementNode', $node));
        }

        $code = $this->compileVarAccess($node->arguments, $localVars);

        return '<?= ' . $code . '?>';
    }

    /**
     * Рендерит директиву raw в интерпретируемом режиме без экранирования.
     *
     * Извлекает значение из контекста и возвращает его КАК ЕСТЬ.
     * Ответственность за безопасность контента лежит на источнике данных.
     *
     * @param StatementNode          $node      Узел выражения
     * @param NodeProcessorInterface $processor Процессор узлов (не используется)
     * @param array<string, mixed>   $context   Контекст переменных
     *
     * @return string Значение переменной без HTML-экранирования
     *
     * @throws RenderingException Если передан узел неверного типа
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        // @phpstan-ignore instanceof.alwaysTrue
        if (!$node instanceof StatementNode) {
            throw new RenderingException($this->getErrorMessage('StatementNode', $node));
        }

        return $this->resolveValue($context, $node->arguments, '', 'StatementNode');
    }
}
