<?php

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Ast\DefaultParser;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Contracts\Core\Ast\ParserInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\RendererInterface;
use Vasoft\Joke\Templator\Contracts\Core\Ast\TagHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Core\LexerInterface;
use Vasoft\Joke\Templator\Contracts\Core\TemplateEngineInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

class TemplateEngine implements TemplateEngineInterface
{

    private ?LexerInterface $lexer;
    private ?ParserInterface $parser;
    private ?RendererInterface $renderer;
    private ?CompilerInterface $compiler;

    public function __construct(ServiceContainer $serviceContainer)
    {
        $this->lexer = $serviceContainer->get(LexerInterface::class);
        if ($this->lexer === null) {
            $this->lexer = new DefaultLexer();
        }
        $this->parser = $serviceContainer->get(ParserInterface::class);
        if ($this->parser === null) {
            $this->parser = new DefaultParser();
        }
        $this->renderer = $serviceContainer->get(RendererInterface::class);
        if ($this->renderer === null) {
            $this->renderer = new DefaultRenderer();
        }
        $this->compiler = $serviceContainer->get(CompilerInterface::class);
        if ($this->compiler === null) {
            $this->compiler = new DefaultCompiler();
        }
    }

    /**
     * @inheritDoc
     */
    public function registerTag(string $tagName, TagHandlerInterface $handler): void
    {
        $this->renderer->registerTag($tagName, $handler);
    }

    /**
     * @inheritDoc
     */
    public function renderString(string $template, array $context): string
    {
        try {
            $tokens = $this->lexer->tokenize($template);
            $ast = $this->parser->parse($tokens);
            $optimizedAst = $this->renderer->optimizeStaticNodes($ast, $context);
            return $this->compiler->compile($optimizedAst);
        } catch (\Throwable $e) {
            if ($e instanceof TemplatorException) {
                throw $e;
            }
            throw new TemplatorException('Error rendering template: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function renderFile(string $path, array $context): string
    {
        if (!file_exists($path)) {
            throw new TemplatorException("Template file not found: $path");
        }
        $template = file_get_contents($path);
        if ($template === false) {
            throw new TemplatorException("Unable to read template file: $path");
        }
        return $this->renderString($template, $context);
    }

}