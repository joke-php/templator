<?php

namespace Vasoft\Joke\Templator\Compiler\Tag;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\TagCompilerInterface;

class IfCompiler implements TagCompilerInterface
{

    /**
     * @inheritDoc
     */
    public function getTagName(): string
    {
        return 'if';
    }

    /**
     * @inheritDoc
     */
    public function compile(TagNode $node, CompilerInterface $compiler): string
    {
        if (!isset($node->attributes['condition'])) {
            throw new \Exception("Missing 'condition' attribute in <{$node->fullTagName}>");
        }
        $condition = $this->generateCondition($node->attributes['condition']);
        $body = '';
        foreach ($node->children as $child) {
            $body .= $compiler->compileNode($child);
        }
        return "if ({$condition}) {\n{$body}}\n";
    }

    private function generateCondition(string $path): string
    {
        return "(bool)(" . $this->generateArrayAccess($path) . ")";
    }

    private function generateArrayAccess(string $path): string
    {
        $keys = explode('.', $path);
        $code = '$context';
        foreach ($keys as $key) {
            $code .= "['" . addslashes($key) . "']";
        }
        return $code;
    }
}