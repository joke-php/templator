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
 * Обработчик директивы цикла (Each Handler).
 *
 * Отвечает за логику выполнения конструкций вида:
 * {% foreach item in list %} ... {% /foreach %}
 * или
 * {% foreach key, value in list %} ... {% /foreach %}
 *
 * Поддерживает компиляцию в PHP-код (с оптимизацией через локальные переменные)
 * и интерпретацию (рендеринг) цикла.
 */
class EachHandler extends NodeHandler
{
    /**
     * {@inheritDoc}
     *
     * Генерирует PHP-код для конструкции foreach.
     * Определяет переменные цикла как локальные для оптимизированного доступа к ним
     * внутри тела цикла.
     *
     * @throws CompileException если синтаксис аргументов неверен или передан узел неверного типа
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

        [$valueVar, $keyVar, $path] = $this->parseArguments($node->arguments, CompileException::class);

        $arrayAccess = $this->compileVarAccess($path, $localVars);
        $localVars = [$valueVar];
        if ('' !== $keyVar) {
            $localVars[] = $keyVar;
        }
        if ('' !== $keyVar) {
            $result = "<?php foreach ({$arrayAccess} as \${$keyVar} => \${$valueVar}): ?>";
        } else {
            $result = "<?php foreach ({$arrayAccess} as \${$valueVar}): ?>";
        }

        $result .= $processor->process($node->children, $context, $localVars);

        return $result . '<?php endforeach; ?>';
    }

    /**
     * Парсит аргументы директивы foreach.
     *
     * Ожидает формат: "variable in expression" или "key, variable in expression".
     *
     * @param string       $value          строка аргументов из узла AST
     * @param class-string $exceptionClass Класс исключения
     *
     * @return array{0: non-empty-string, 1: string, 2: non-empty-string} массив, содержащий:
     *                                                                    [0] - имя переменной значения ($valueVar),
     *                                                                    [1] - имя переменной ключа ($keyVar, может быть пустым),
     *                                                                    [2] - путь к итерируемому выражению ($path)
     *
     * @throws CompileException|RenderingException если синтаксис не соответствует ожидаемому формату
     */
    private function parseArguments(string $value, string $exceptionClass): array
    {
        $cleanValue = trim($value);
        if (!preg_match('#^(\w+)(?:\s*,\s*(\w+))?\s+in\s+(.+)$#', $cleanValue, $matches)) {
            /** @var CompileException|RenderingException $exception */
            $exception = new $exceptionClass("Invalid foreach syntax: '{$value}'.");

            throw $exception;
        }
        $keyVar = $matches[1];
        $valueVar = $matches[2];
        if ('' === $valueVar) {
            $valueVar = $keyVar;
            $keyVar = '';
        }

        return [$valueVar, $keyVar, $matches[3]];
    }

    /**
     * {@inheritDoc}
     *
     * Выполняет цикл в режиме интерпретации.
     * Для каждой итерации создает обновленный контекст с локальными переменными цикла
     * и рекурсивно рендерит дочерние узлы.
     *
     * @throws RenderingException если передан узел неверного типа или ошибка при доступе к данным
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): string {
        if (!$node instanceof BlockNode) {
            throw new RenderingException($this->getErrorMessage('BlockNode', $node));
        }
        [$valueVar, $keyVar, $path] = $this->parseArguments($node->arguments, RenderingException::class);

        $items = $this->resolveValue($context, $path, [], $node->directive);
        $output = '';
        foreach ($items as $index => $item) {
            $iterationContext = $context;
            $iterationContext[$valueVar] = $item;
            if ('' !== $keyVar) {
                $iterationContext[$keyVar] = $index;
            }
            $output .= $processor->process($node->children, $iterationContext);
        }

        return $output;
    }
}
