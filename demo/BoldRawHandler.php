<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;

class BoldRawHandler extends NodeHandler
{
    /**
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

        return "<?=  '<b>'.{$code}.'</b>' ?>";
    }

    /**
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

        return '<b>' . $this->resolveValue($context, $node->arguments, '', 'StatementNode') . '</b>';
    }
}
