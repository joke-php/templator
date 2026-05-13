<?php

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Contracts\TemplateEngineInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

class TemplateEngine implements TemplateEngineInterface
{
    public function __construct(private readonly ServiceContainer $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function renderString(string $template, array $context): string
    {
        try {
            $lexer = $this->container->get(LexerInterface::class);
            $tokens = $lexer->tokenize($template);

            $parser = $this->container->get(ParserInterface::class);
            $ast = $parser->parse($tokens);

            $compiler = $this->container->get('templator.compiler');
            return $compiler->process($ast, $context);
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