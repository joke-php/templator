<?php

namespace Vasoft\Joke\Templator\Contracts\Core\Compiler;

use Vasoft\Joke\Templator\Ast\TagNode;

interface TagCompilerInterface
{
    /**
     * Возвращает имя тега, который обрабатывает этот компилятор
     * @return string Например: 'echo', 'if'
     */
    public function getTagName(): string;

    /**
     * Генерирует PHP-код для тега
     * @param TagNode $node Узел AST
     * @param CompilerInterface $compiler Ссылка на основной компилятор (для рекурсии)
     * @return string PHP-код
     */
    public function compile(TagNode $node, CompilerInterface $compiler): string;
}