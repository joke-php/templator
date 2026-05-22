<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

/**
 * Обработчик условной директивы (If Handler).
 *
 * Отвечает за логику выполнения конструкций вида:
 * {% if condition %} ... {% elseif condition %} ... {% else %} ... {% /if %}
 *
 * Поддерживает компиляцию в PHP-код и интерпретацию условия.
 */
class IfHandler extends NodeHandler
{
    /**
     * {@inheritDoc}
     *
     * Генерирует PHP-код для условной конструкции.
     * Рекурсивно компилирует дочерние узлы основной ветки и всех ветвлений (elseif/else).
     *
     * @throws CompileException если передан узел неверного типа или условие пустое
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        if (!$node instanceof BlockNode) {
            throw new CompileException($this->getErrorMessage('BlockNode', $node));
        }

        $result = '<?php if(' . $this->generateCondition($node->arguments, $localVars, 'if') . '): ?>';
        $result .= $processor->process($node->children, $context, $localVars);
        foreach ($node->branches as $branch) {
            if ('else' === $branch->name) {
                $result .= $this->compileElse($processor, $branch->children, $context, $localVars);
            } elseif ('elseif' === $branch->name) {
                $result .= $this->compileElseIf(
                    $processor,
                    $branch->arguments,
                    $branch->children,
                    $context,
                    $localVars,
                );
            }
        }
        $result .= '<?php endif; ?>';

        return $result;
    }

    /**
     * Компилирует ветку `else`.
     *
     * @param NodeProcessorInterface $processor процессор для рекурсивной обработки детей
     * @param list<NodeInterface>    $children  дочерние узлы ветки else
     * @param array<string, mixed>   $context   данные контекста
     * @param list<string>           $localVars список локальных переменных
     *
     * @return string скомпилированный PHP-код для ветки else
     */
    private function compileElse(
        NodeProcessorInterface $processor,
        array $children,
        array $context,
        array $localVars,
    ): string {
        $result = '<?php else: ?>';

        return $result . $processor->process($children, $context, $localVars);
    }

    /**
     * Компилирует ветку `elseif`.
     *
     * @param NodeProcessorInterface $processor процессор для рекурсивной обработки детей
     * @param string                 $arguments строка условия для elseif
     * @param list<NodeInterface>    $children  дочерние узлы ветки elseif
     * @param array<string, mixed>   $context   данные контекста
     * @param list<string>           $localVars список локальных переменных
     *
     * @return string скомпилированный PHP-код для ветки elseif
     *
     * @throws CompileException если условие пустое
     */
    private function compileElseIf(
        NodeProcessorInterface $processor,
        string $arguments,
        array $children,
        array $context,
        array $localVars,
    ): string {
        $result = '<?php elseif(' . $this->generateCondition($arguments, $localVars, 'elseif') . '): ?>';

        return $result . $processor->process($children, $context, $localVars);
    }

    /**
     * Генерирует безопасное PHP-выражение для условия.
     *
     * Преобразует путь к переменной в код доступа (прямой или через массив контекста)
     * и оборачивает его в приведение к типу bool.
     *
     * @param string       $path      путь к переменной или выражение
     * @param list<string> $localVars список локальных переменных
     * @param string       $directive имя директивы (для сообщения об ошибке)
     *
     * @return string PHP-код условия, готовый для вставки в конструкцию if/elseif
     *
     * @throws CompileException если передан пустой аргумент условия
     */
    private function generateCondition(string $path, array $localVars, string $directive): string
    {
        if ('' === trim($path)) {
            throw new CompileException("Directive '{$directive}' with no arguments.");
        }

        return '(bool)(' . $this->compileVarAccess($path, $localVars) . ')';
    }

    /**
     * {@inheritDoc}
     *
     * Выполняет условную логику в режиме интерпретации.
     * Последовательно проверяет условия if/elseif и возвращает результат рендеринга
     * первой подходящей ветки. Если ни одно условие не выполнено, возвращает результат ветки else или пустую строку.
     *
     * @throws RenderingException если передан узел неверного типа
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        if (!$node instanceof BlockNode) {
            throw new RenderingException($this->getErrorMessage('BlockNode', $node));
        }
        $value = $this->resolveValue($context, $node->arguments, false, $node->directive);
        if ($value) {
            return $processor->process($node->children, $context);
        }
        foreach ($node->branches as $branch) {
            if ('else' === $branch->name) {
                return $processor->process($branch->children, $context);
            }
            if ('elseif' === $branch->name) {
                $value = $this->resolveValue($context, $branch->arguments, false, $branch->name);
                if ($value) {
                    return $processor->process($branch->children, $context);
                }
            }
        }

        return '';
    }
}
