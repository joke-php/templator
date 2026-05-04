<?php

namespace Vasoft\Joke\Templator\Compiler;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Ast\TextNode;
use Vasoft\Joke\Templator\Compiler\Tag\EchoCompiler;
use Vasoft\Joke\Templator\Compiler\Tag\IfCompiler;
use Vasoft\Joke\Templator\Contracts\Ast\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\NodeCompilerInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\TagCompilerInterface;

class DefaultCompiler implements CompilerInterface
{
    /** @var array<string, string> $tagCompilers [tagName => compilerClass] */
    private array $tagCompilers = [];

    /** @var array<string, string> $nodeCompilers [nodeClass => compilerClass] */
    private array $nodeCompilers = [];

    /** @var array<string, NodeCompilerInterface> $instantiatedNodeCompilers */
    private array $instantiatedNodeCompilers = [];

    /** @var array<string, TagCompilerInterface> $instantiatedTagCompilers */
    private array $instantiatedTagCompilers = [];

    public function __construct()
    {
        $this->registerTagCompiler('echo', EchoCompiler::class);
        $this->registerTagCompiler('if', IfCompiler::class);
    }

    public function registerTagCompiler(string $tagName, string $compilerClass): static
    {
        if (!is_a($compilerClass, TagCompilerInterface::class, true)) {
            throw new \InvalidArgumentException("Compiler must implement TagCompilerInterface");
        }
        $this->tagCompilers[$tagName] = $compilerClass;
        return $this;
    }

    public function registerNodeCompiler(string $nodeClass, string $compilerClass): static
    {
        if (!is_a($compilerClass, NodeCompilerInterface::class, true)) {
            throw new \InvalidArgumentException("Compiler must implement NodeCompilerInterface");
        }
        $this->nodeCompilers[$nodeClass] = $compilerClass;
        return $this;
    }

    public function compile(array $ast): string
    {
        $code = '';
        foreach ($ast as $node) {
            $code .= $this->compileNode($node);
        }
        return $code;
    }

    public function compileNode(NodeInterface $node): string
    {
        if ($node instanceof TextNode) {
            return $node->content;
        }
        /** @var TagNode $node */
        if (isset($this->tagCompilers[$node->tagName])) {
            $compiler = $this->getTagCompiler($node->tagName);
            return $compiler->compile($node, $this);
        }
        throw new \Exception("No compiler registered for tag '{$node->fullTagName}'");
    }

    private function getNodeCompiler(string $nodeClass): NodeCompilerInterface
    {
        if (!isset($this->instantiatedNodeCompilers[$nodeClass])) {
            $class = $this->nodeCompilers[$nodeClass];
            $this->instantiatedNodeCompilers[$nodeClass] = new $class();
        }
        return $this->instantiatedNodeCompilers[$nodeClass];
    }

    private function getTagCompiler(string $tagName): TagCompilerInterface
    {
        if (!isset($this->instantiatedTagCompilers[$tagName])) {
            $class = $this->tagCompilers[$tagName];
            $this->instantiatedTagCompilers[$tagName] = new $class();
        }
        return $this->instantiatedTagCompilers[$tagName];
    }
}