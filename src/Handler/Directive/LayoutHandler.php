<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Support\FileSystem;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\TemplateEngine;

class LayoutHandler extends NodeHandler
{
    public function __construct(
        private readonly TemplateEngine $engine,
        private readonly FileSystem $fs,
    ) {}

    /**
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
        $layoutName = explode(' ', trim($node->arguments), 1)[0];
        $filename = $this->fs->at($this->engine->layoutsPath, $layoutName . '.php');

        $innerPhpCode = $processor->process($node->children, $context, $localVars);

        return <<<PHP
                <?php
                ob_start();
                ?>
                {$innerPhpCode}
                <?php
                 \$__content = ob_get_clean();
                \$__layoutContext = ['__layout' => ['content' => \$__content]];
                \$engine->includeFile('{$filename}',\$__layoutContext,0);
                ?>
            PHP;
    }

    /**
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

        throw new RenderingException('Not implemented yet.');
    }
}
